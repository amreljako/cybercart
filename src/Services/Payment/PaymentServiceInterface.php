<?php

namespace Amreljako\CyberCart\Services\Payment;

use Amreljako\CyberCart\Models\Order;
use Illuminate\Http\Request;

interface PaymentServiceInterface
{
    /**
     * Initiate the payment process with the provider and return redirect URL or payment payload.
     */
    public function initiatePayment(Order $order): array;

    /**
     * Handle and verify the asynchronous incoming Webhook from the payment gateway securely.
     */
    public function verifyWebhook(Request $request): array;
}