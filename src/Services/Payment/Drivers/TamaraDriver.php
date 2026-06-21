<?php

namespace Amreljako\CyberCart\Services\Payment\Drivers;

use Amreljako\CyberCart\Services\Payment\PaymentServiceInterface;
use Amreljako\CyberCart\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;

class TamaraDriver implements PaymentServiceInterface
{
    protected string $token;
    protected string $apiUrl;

    public function __construct()
    {
        $this->token = config('cybercart.payments.tamara.api_token');
        $this->apiUrl = config('cybercart.payments.tamara.sandbox') 
            ? 'https://api-sandbox.tamara.co' 
            : 'https://api.tamara.co';
    }

    public function initiatePayment(Order $order): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception("Paymob API Key is missing. Please configure it in your .env file.");
        }
        if (empty($this->token)) {
            throw new \Exception("Paymob token is missing. Please configure it in your .env file.");
        }
        // Tamara requires complex structural payload detailing all individual bought line items
        $items = [];
        foreach ($order->items as $item) {
            $items[] = [
                'name' => $item->product_title,
                'sku' => $item->variant_sku,
                'quantity' => (int)$item->quantity,
                'total_amount' => [
                    'amount' => (float)$item->total_price,
                    'currency' => 'SAR'
                ]
            ];
        }

        $response = Http::withToken($this->token)->post("{$this->apiUrl}/checkout", [
            'order_number' => $order->order_number,
            'total_amount' => [
                'amount' => (float)$order->grand_total,
                'currency' => 'SAR'
            ],
            'description' => "Order #{$order->order_number} inside CyberCart",
            'country_code' => 'SA',
            'payment_type' => 'PAY_BY_INSTALMENTS',
            'items' => $items,
            'consumer' => [
                'first_name' => $order->shipping_address['first_name'] ?? 'Customer',
                'last_name' => $order->shipping_address['last_name'] ?? 'User',
                'phone_number' => $order->shipping_address['phone'] ?? '+966500000000',
                'email' => $order->shipping_address['email'] ?? 'customer@tamara.co'
            ],
            'merchant_url' => [
                'success' => route('cybercart.payment.success', ['order_id' => $order->id]),
                'failure' => route('cybercart.payment.failure', ['order_id' => $order->id]),
                'cancel' => route('cybercart.payment.cancel', ['order_id' => $order->id]),
                'notification' => route('cybercart.payment.webhook')
            ]
        ]);

        if ($response->failed()) {
            throw new Exception("Tamara Checkout registration failed: " . $response->body());
        }

        return [
            'redirect_url' => $response->json()['checkout_url'],
            'transaction_id' => $response->json()['order_id']
        ];
    }

    /**
     * Tamara secures endpoints using explicit custom symmetric Authorization tokens
     */
    public function verifyWebhook(Request $request): array
    {
        // Guard checking the shared token authorization mapping header
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !hash_equals("Bearer " . $this->token, $authHeader)) {
            return ['status' => 'failed', 'message' => 'Unauthorized Webhook Caller Source'];
        }

        $data = $request->json()->all();
        $isSuccess = isset($data['event_type']) && $data['event_type'] === 'order_approved';

        return [
            'status' => $isSuccess ? 'success' : 'failed',
            'order_number' => $data['order_number'] ?? null,
            'transaction_id' => $data['order_id'] ?? null,
        ];
    }
}