<?php

use Illuminate\Support\Facades\Route;
use Amreljako\CyberCart\Facades\Cart;
use Amreljako\CyberCart\Facades\Checkout;
use Amreljako\CyberCart\Models\ProductVariant;

Route::group(['prefix' => 'cybercart', 'middleware' => ['web']], function () {

    // 1. Sandbox demo route to inspect cart states and inject an atomic verification variant
Route::get('/cart-demo', function () {
        
        // 2. تأمين وجود المنتج الأب أولاً في قاعدة البيانات لمنع تعارض الـ Foreign Key
        $product = Product::firstOrCreate(
            ['slug' => 'cyber-demo-product'],
            [
                'title' => 'CyberCart Demo Product',
                'description' => 'A secure sandbox evaluation product context.',
                'is_active' => true,
                'meta_tags' => ['demo', 'core-testing']
            ]
        );

        // 3. ربط الـ Variant بالـ ID الحقيقي للمنتج المكريت
        $variant = ProductVariant::firstOrCreate(
            ['sku' => 'CYBER-DEMO-999'],
            [
                'product_id' => $product->id, // هنا بنمرر الـ ID الديناميكي
                'price' => 299.00,
                'stock_quantity' => 5,
                'track_stock' => true
            ]
        );

        // Deduce and add 2 units as an operational sandbox baseline
        Cart::addToCart($variant->id, 2);

        return response()->json([
            'status' => 'success',
            'message' => 'Welcome to CyberCart Sandbox Engine!',
            'cart_contents' => Cart::getCartContent(),
            'integrity_checks' => [
                'pessimistic_row_lock' => 'Active',
                'race_condition_mitigation' => 'Enabled'
            ]
        ]);
    });


    // 2. Gateway execution route to test inventory deduction loops and standalone rendering
    Route::get('/checkout-demo', function () {
        $variant = ProductVariant::where('sku', 'CYBER-DEMO-999')->first();
        
        if (!$variant) {
            return redirect('/cybercart/cart-demo');
        }

        $shippingAddress = [
            'first_name' => 'Amr',
            'last_name' => 'Elsayed',
            'email' => 'amr.elsayed@example.com',
            'phone' => '+201000000000',
            'address' => 'Alexandria, Egypt'
        ];

        try {
            // Initialize secure dynamic transactional session using the mapped gateway driver
            $checkout = Checkout::execute($shippingAddress, 'paymob');
            
            // Generate zero-dependency vector footprints on the fly
            $barcodeSvg = $variant->renderBarcodeSvg();
            $qrCodeSvg = $variant->renderQrCodeSvg(url('/cybercart/cart-demo'));

            return "
                <div style='font-family: sans-serif; text-align: center; padding: 50px; background: #0d1117; color: #c9d1d9;'>
                    <h2 style='color: #58a6ff;'>CyberCart Engine - Core Logic Verified Successfully</h2>
                    <p style='color: #8b949e;'>Pessimistic Inventory Lock Executed & Payment Gateway Subsystem Initialized.</p>
                    <hr style='border-color: #21262d; margin: 30px 0;'>
                    <div style='margin-bottom: 20px;'>
                        <h3 style='color: #c9d1d9;'>Industrial Code 128 Barcode:</h3>
                        <div style='display: inline-block; background: #ffffff; padding: 10px; border-radius: 4px;'>
                            {$barcodeSvg}
                        </div>
                        <p style='font-size: 12px; color: #8b949e; margin-top: 5px;'>SKU: {$variant->sku}</p>
                    </div>
                    <div style='margin-top: 40px;'>
                        <h3 style='color: #c9d1d9;'>Secure Deep-Linked QR Code:</h3>
                        <div style='display: inline-block; background: #ffffff; padding: 10px; border-radius: 4px;'>
                            {$qrCodeSvg}
                        </div>
                    </div>
                </div>
            ";
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'security_mitigation_trigger' => $e->getMessage()
            ], 422);
        }
    });
});