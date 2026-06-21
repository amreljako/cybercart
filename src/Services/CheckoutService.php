<?php

namespace Amreljako\CyberCart\Services;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Amreljako\CyberCart\Models\Order;
use Amreljako\CyberCart\Models\OrderItem;
use Amreljako\CyberCart\Models\ProductVariant;
use Amreljako\CyberCart\Facades\Cart;
use Amreljako\CyberCart\Services\Payment\PaymentManager;

class CheckoutService
{
    protected PaymentManager $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * Convert current cart into a secured legal order and deduce inventory stock.
     *
     * @param array $shippingAddress
     * @param string $paymentDriver
     * @return array
     * @throws Exception
     */
    public function execute(array $shippingAddress, string $paymentDriver): array
    {
        $cartContent = Cart::getCartContent();

        if (empty($cartContent['items'])) {
            throw new Exception("Cannot checkout with an empty cart.");
        }

        return DB::transaction(function () use ($cartContent, $shippingAddress, $paymentDriver) {
            
            // 1. Create the base Order record
            $order = new Order();
            $order->order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            
            // Polymorphic relation config mapping
            $order->customer_type = config('cybercart.customer_model', 'App\Models\User');
            $order->customer_id = auth()->id() ?? 0; // Supports Guests as 0
            
            $order->subtotal = $cartContent['subtotal'];
            $order->discount_total = session()->get('cybercart_discount', 0.00);
            $order->shipping_total = config('cybercart.shipping_flat_rate', 0.00);
            $order->grand_total = ($order->subtotal + $order->shipping_total) - $order->discount_total;
            
            $order->payment_driver = $paymentDriver;
            $order->payment_status = 'pending';
            $order->order_status = 'pending';
            $order->shipping_address = $shippingAddress;
            $order->save();

            // 2. Process items, verify stock availability, and acquire structural DB lock
            foreach ($cartContent['items'] as $item) {
                $variant = ProductVariant::where('id', $item['variant_id'])
                    ->lockForUpdate()
                    ->first();

                if ($variant->track_stock) {
                    if ($variant->stock_quantity < $item['quantity']) {
                        throw new Exception("Stock compromised for item: {$item['title']}. Race condition mitigated.");
                    }
                    
                    // Deduce stock permanently
                    $variant->decrement('stock_quantity', $item['quantity']);
                }

                // Create individual order item snapshot
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $variant->id,
                    'product_title' => $item['title'],
                    'variant_sku' => $variant->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $item['total'],
                ]);
            }

            // 3. Fire designated Payment Driver initialization
            $paymentResult = $this->paymentManager->driver($paymentDriver)->initiatePayment($order);
            
            // Update temporary transaction metadata
            $order->update([
                'payment_transaction_id' => $paymentResult['transaction_id'] ?? null
            ]);

            // Flush secure cart session after successful processing
            session()->forget(['cybercart', 'cybercart_discount']);

            return [
                'success' => true,
                'order_number' => $order->order_number,
                'redirect_url' => $paymentResult['redirect_url']
            ];
        });
    }

    /**
     * Finite State Machine (FSM) restriction layer for transitional security
     */
    public function transitionOrderStatus(Order $order, string $newStatus): void
    {
        $validTransitions = [
            'pending'    => ['processing', 'cancelled'],
            'processing' => ['shipped'],
            'shipped'    => ['delivered'],
            'cancelled'  => [],
            'delivered'  => []
        ];

        if (!in_array($newStatus, $validTransitions[$order->order_status] ?? [])) {
            throw new Exception("Invalid order status transition from [{$order->order_status}] to [{$newStatus}].");
        }

        $order->update(['order_status' => $newStatus]);
    }
}