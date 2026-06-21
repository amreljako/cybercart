<?php

namespace Amreljako\CyberCart\Services\Payment;

use Illuminate\Support\Manager;
use Amreljako\CyberCart\Services\Payment\Drivers\PaymobDriver;
use Amreljako\CyberCart\Services\Payment\Drivers\TamaraDriver;
use InvalidArgumentException;

class PaymentManager extends Manager
{
    /**
     * Get the default driver name configured in config file.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('cybercart.payment_driver', 'stripe');
    }

    /**
     * Create Paymob Driver instance.
     */
    public function createPaymobDriver(): PaymentServiceInterface
    {
        return new PaymobDriver();
    }

    /**
     * Create Tamara Driver instance.
     */
    public function createTamaraDriver(): PaymentServiceInterface
    {
        return new TamaraDriver();
    }

    // You can dynamically expand drivers here (e.g., fawaterk, easypay, mada) without changing core code structure
}