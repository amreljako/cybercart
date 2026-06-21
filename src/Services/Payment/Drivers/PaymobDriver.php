<?php

namespace Amreljako\CyberCart\Services\Payment\Drivers;

use Amreljako\CyberCart\Services\Payment\PaymentServiceInterface;
use Amreljako\CyberCart\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;

class PaymobDriver implements PaymentServiceInterface
{
    protected string $apiKey;
    protected string $integrationId;
    protected string $hmacSecret;

    public function __construct()
    {
        $this->apiKey = config('cybercart.payments.paymob.api_key');
        $this->integrationId = config('cybercart.payments.paymob.integration_id');
        $this->hmacSecret = config('cybercart.payments.paymob.hmac_secret');
    }

    public function initiatePayment(Order $order): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception("Paymob API Key is missing. Please configure it in your .env file.");
        }
        // Step 1: Authentication Request
        $authResponse = Http::post('https://accept.paymob.com/api/auth/tokens', [
            'api_key' => $this->apiKey
        ]);

        if ($authResponse->failed()) {
            throw new Exception("Paymob Authentication failed.");
        }

        $token = $authResponse->json()['token'];

        // Step 2: Order Registration
        $orderResponse = Http::post('https://accept.paymob.com/api/ecommerce/orders', [
            'auth_token' => $token,
            'delivery_needed' => 'false',
            'amount_cents' => (int)($order->grand_total * 100), // Convert decimal currency to cents
            'currency' => 'EGP',
            'merchant_order_id' => $order->order_number,
        ]);

        if ($orderResponse->failed()) {
            throw new Exception("Paymob order registration failed.");
        }

        $paymobOrderId = $orderResponse->json()['id'];

        // Step 3: Get Payment Key / Redirect URL
        $paymentKeyResponse = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', [
            'auth_token' => $token,
            'amount_cents' => (int)($order->grand_total * 100),
            'expiration' => 3600,
            'order_id' => $paymobOrderId,
            'billing_data' => [
                'first_name' => $order->shipping_address['first_name'] ?? 'Guest',
                'last_name' => $order->shipping_address['last_name'] ?? 'User',
                'email' => $order->shipping_address['email'] ?? 'guest@cybercart.com',
                'phone_number' => $order->shipping_address['phone'] ?? '+201000000000',
                'apartment' => 'NA', 'floor' => 'NA', 'street' => 'NA', 'building' => 'NA',
                'shipping_method' => 'PKG', 'postal_code' => 'NA', 'city' => 'Cairo', 'country' => 'EG', 'state' => 'NA'
            ],
            'currency' => 'EGP',
            'integration_id' => $this->integrationId
        ]);

        $paymentToken = $paymentKeyResponse->json()['token'];

        return [
            'redirect_url' => "https://accept.paymob.com/api/acceptance/iframes/" . config('cybercart.payments.paymob.iframe_id') . "?payment_token=" . $paymentToken,
            'transaction_id' => $paymobOrderId
        ];
    }

    /**
     * Secure Webhook verification using explicit HMAC verification signature matching
     */
    public function verifyWebhook(Request $request): array
    {
        $data = $request->json()->all();
        $obj = $data['obj'] ?? [];

        // Cryptographic integrity check: Paymob concatenates structural payload values to hash
        $hmacString = 
            ($obj['amount_cents'] ?? '') .
            ($obj['created_at'] ?? '') .
            ($obj['currency'] ?? '') .
            ($obj['error_occured'] === true ? 'true' : 'false') .
            ($obj['has_parent_transaction'] === true ? 'true' : 'false') .
            ($obj['id'] ?? '') .
            ($obj['integration_id'] ?? '') .
            ($obj['is_3d_secure'] === true ? 'true' : 'false') .
            ($obj['is_auth'] === true ? 'true' : 'false') .
            ($obj['is_capture'] === true ? 'true' : 'false') .
            ($obj['is_standalone_payment'] === true ? 'true' : 'false') .
            ($obj['is_voided'] === true ? 'true' : 'false') .
            ($obj['is_refunded'] === true ? 'true' : 'false') .
            ($obj['order']['id'] ?? '') .
            ($obj['owner'] ?? '') .
            ($obj['pending'] === true ? 'true' : 'false') .
            ($obj['source_data']['pan'] ?? '') .
            ($obj['source_data']['sub_type'] ?? '') .
            ($obj['source_data']['type'] ?? '') .
            ($obj['success'] === true ? 'true' : 'false');

        $calculatedHmac = hash_hmac('sha512', $hmacString, $this->hmacSecret);
        $receivedHmac = $request->query('hmac');

        // Constant-time string comparison to prevent side-channel timing attacks
        if (!hash_equals($calculatedHmac, $receivedHmac)) {
            return ['status' => 'failed', 'message' => 'HMAC Signature Tampering Detected'];
        }

        $isSuccess = $obj['success'] === true;

        return [
            'status' => $isSuccess ? 'success' : 'failed',
            'order_number' => $obj['order']['merchant_order_id'] ?? null,
            'transaction_id' => $obj['id'] ?? null,
        ];
    }
}