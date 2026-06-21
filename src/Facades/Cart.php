<?php

namespace Amreljako\CyberCart\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array addToCart(int $variantId, int $quantity)
 * @method static array getCartContent()
 * * @see \Amreljako\CyberCart\Services\CartService
 */
class Cart extends Facade
{
    /**
     * Get the registered name of the component inside the Service Container.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cybercart.cart';
    }
}