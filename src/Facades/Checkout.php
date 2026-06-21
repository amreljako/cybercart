<?php

namespace Amreljako\CyberCart\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array execute(array $shippingAddress, string $paymentDriver)
 * @method static void transitionOrderStatus(\Amreljako\CyberCart\Models\Order $order, string $newStatus)
 * * @see \Amreljako\CyberCart\Services\CheckoutService
 */
class Checkout extends Facade
{
    /**
     * Get the registered name of the component inside the Service Container.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cybercart.checkout';
    }
}