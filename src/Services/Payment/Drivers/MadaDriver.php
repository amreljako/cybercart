<?php

namespace Amreljako\CyberCart\Services\Payment\Drivers;

use Amreljako\CyberCart\Services\Payment\PaymentServiceInterface;
use Amreljako\CyberCart\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;

class MadaDriver implements PaymentServiceInterface
{
    protected string $secretKey;
    protected string $apiUrl;

    public function __construct()
    {
        // Mada is usually processed through payment facilitators like Moyasar
        $this->secretKey = config('cybercart.payments.mada.secret_key');
        $this->apiUrl = config('cybercart.payments.mada.sandbox', true)
            ? 'https://api.moyasar.com/v1/payments'
            : 'https://api.moyasar.com/v1/payments';
    }

    public function initiatePayment(Order $order): array
    {
        $response = Http::withBasicAuth($this->secretKey, '')
            ->post($this->apiUrl, [
                'amount' => (int)($order->grand_total * 100), // Convert SAR to Halalas (cents)
                'currency' => 'SAR',
                'description' => "Order #{$order->order_number} via Mada",
                'callback_url' => route('cybercart.payment.callback', ['order_id' => $order->id]),
                'source' => [
                    'type' => 'creditcard',
                    'company' => 'mada' // Forces the local payment network engine validation
                ],
                'metadata' => [
                    'order_number' => $order->order_number
                ]
            ]);

        if ($response->failed()) {
            throw new Exception("Mada Payment Initiation Failed: " . $response->body());
        }

        $responseData = $response->json();

        return [
            'redirect_url' => $responseData['source']['transaction_url'] ?? null,
            'transaction_id' => $responseData['id'] ?? null
        ];
    }

    /**
     * Verify incoming Mada transaction events securely using hash signatures
     */
    public function verifyWebhook(Request $request): array
    {
        // Secure token authentication matching the header signature
        $signature = $request->header('X-Mada-Signature');
        $payload = $request->getContent();

        if (!$signature) {
            return ['status' => 'failed', 'message' => 'Missing security signature header.'];
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->secretKey);

        // Mitigate side-channel timing attacks
        if (!hash_equals($expectedSignature, $signature)) {
            return ['status' => 'failed', 'message' => 'Signature verification failed. Potential tampering.'];
        }

        $data = $request->json()->all();
        $isSuccess = isset($data['status']) && $data['status'] === 'captured';

        return [
            'status' => $isSuccess ? 'success' : 'failed',
            'order_number' => $data['metadata']['order_number'] ?? null,
            'transaction_id' => $data['id'] ?? null,
        ];
    }
}