<?php

namespace Amreljako\CyberCart\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Amreljako\CyberCart\Models\ProductVariant;

class CartService
{
    /**
     * Add an item to the cart safely protected against Race Conditions.
     *
     * @param int $variantId
     * @param int $quantity
     * @return array
     * @throws Exception
     */
    public function addToCart(int $variantId, int $quantity): array
    {
        //  Prevent negative or zero quantities
        if ($quantity <= 0) {
            throw new Exception("Requested quantity must be 1 or more.");
        }

        // Start DB Transaction with Pessimistic Locking to eliminate concurrent data manipulation
        return DB::transaction(function () use ($variantId, $quantity) {
            
            // Acquire raw row lock until the transaction commits
            $variant = ProductVariant::where('id', $variantId)
                ->lockForUpdate()
                ->first();

            if (!$variant) {
                throw new Exception("The selected product variant does not exist.");
            }

            // Verify real-time stock availability
            if ($variant->track_stock && $variant->stock_quantity < $quantity) {
                throw new Exception("Sorry, the requested quantity is out of stock.");
            }

            $cart = session()->get('cybercart', []);

            if (isset($cart[$variantId])) {
                $newQuantity = $cart[$variantId]['quantity'] + $quantity;
                
                if ($variant->track_stock && $variant->stock_quantity < $newQuantity) {
                    throw new Exception("Cannot add more items. Stock capacity exceeded.");
                }
                $cart[$variantId]['quantity'] = $newQuantity;
            } else {
                $cart[$variantId] = [
                    'variant_id' => $variant->id,
                    'sku'        => $variant->sku,
                    'title'      => $variant->product->title,
                    'price'      => $variant->price,
                    'quantity'   => $quantity,
                ];
            }

            session()->put('cybercart', $cart);

            return [
                'success' => true,
                'message' => 'Item added to cart successfully.',
                'cart'    => $cart
            ];
        });
    }

    /**
     * Retrieve cart contents with integrity validation directly from the Database.
     */
    public function getCartContent(): array
    {
        $cart = session()->get('cybercart', []);
        $subtotal = 0.00;
        $secureItems = [];

        if (empty($cart)) {
            return ['items' => [], 'subtotal' => 0.00];
        }

        // Re-verify prices against the Database to prevent Client-Side session tampering
        $variantIds = array_keys($cart);
        $variants = ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id');

        foreach ($cart as $id => $item) {
            if (isset($variants[$id])) {
                $realPrice = $variants[$id]->price;
                $itemTotal = $realPrice * $item['quantity'];
                $subtotal += $itemTotal;

                $secureItems[] = [
                    'variant_id' => $id,
                    'title'      => $item['title'],
                    'price'      => $realPrice,
                    'quantity'   => $item['quantity'],
                    'total'      => $itemTotal
                ];
            }
        }

        return [
            'items'    => $secureItems,
            'subtotal' => $subtotal
        ];
    }
}