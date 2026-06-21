# CyberCart

A secure, headless, high-performance embedded e-commerce core engine for Laravel. CyberCart is designed to be dynamically plugged into any existing Laravel application codebase seamlessly, without breaking your current database architecture. It features regional Arab and Gulf payment providers, atomic-locked inventory protection, and complete headless flexibility.

---

## Key Features

**Headless Architecture**
Focuses purely on core backend e-commerce logic, giving you complete freedom to design your UI using Blade, Livewire, Inertia, Vue, or React.

**Race Condition Protection**
Utilizes strict database pessimistic locking (`lockForUpdate`) and atomic database transactions to eliminate concurrent checkout anomalies or double-selling issues during high-traffic flash sales.

**Regional Arab & Gulf Payments**
Native driver-based payment architecture supporting Paymob, Tamara, and Mada out of the box, with secure cryptographic webhook hash/signature validation layers.

**Dynamic Barcode & QR Code Engine**
Zero-dependency, lightweight, on-the-fly generation of industrial barcodes (Code 128 standard) and QR codes as inline scalable vector graphics (SVG).

**Polymorphic Binding Flexibility**
Seamlessly attaches to any existing database structure (Users, Clients, Admins) via custom configurable polymorphic relations.

**Strict State Machine Lifecycle**
Order status transitions strictly enforce integrity boundaries (`Pending -> Processing -> Shipped -> Delivered`), preventing unauthorized workflow mutations.

---

## Directory Structure

```text
cybercart/
├── config/
│   └── cybercart.php               # Global configuration (drivers, model bindings)
├── database/
│   └── migrations/                 # Index-optimized structural migrations
├── src/
│   ├── Facades/
│   │   ├── Cart.php                # Static accessor facade for cart operations
│   │   └── Checkout.php            # Static accessor facade for secure checkouts
│   ├── Models/
│   │   ├── Product.php
│   │   ├── ProductVariant.php
│   │   ├── Order.php
│   │   └── OrderItem.php
│   ├── routes/
│   │   └── web.php                 # Built-in developer demo and verification routes
│   ├── Services/
│   │   ├── CartService.php         # Cart business engine (stock / price integrity checks)
│   │   ├── CheckoutService.php     # Finite state machine implementation for orders
│   │   └── Payment/
│   │       ├── PaymentManager.php           # Driver routing abstraction layer
│   │       ├── PaymentServiceInterface.php  # Unified payment contract signature
│   │       └── Drivers/
│   │           ├── PaymobDriver.php # Egypt flagship payment processor
│   │           ├── TamaraDriver.php # KSA / Gulf split-payment framework
│   │           └── MadaDriver.php   # KSA debit network processing driver
│   ├── Traits/
│   │   └── HasBarcode.php          # Eloquent observer for automatic SKU tracking
│   └── CyberCartServiceProvider.php # Package bootstrapping lifecycle loader
├── composer.json                   # Composer package configuration
└── README.md                       # Package documentation
```

---

## Installation

### 1. Install via Composer

The package is published on Packagist, so it can be installed directly into your Laravel application:

```bash
composer require amreljako/cybercart
```

### 2. Publish the configuration

Export the default configuration file to your application's config directory:

```bash
php artisan vendor:publish --tag=cybercart-config
```

### 3. Run the migrations

Execute the database schema deployment to create the index-optimized e-commerce tables:

```bash
php artisan migrate
```

---

## Configuration

Edit the generated `config/cybercart.php` file to adapt the package to your application:

```php
return [
    // Bind the polymorphic customer entity to your primary application's User model
    'customer_model' => App\Models\User::class,

    // Flat-rate logistics shipping cost default
    'shipping_flat_rate' => 50.00,

    // Default active payment gateway driver
    'payment_driver' => env('CYBERCART_PAYMENT_DRIVER', 'paymob'),

    'payments' => [
        'paymob' => [
            'api_key'        => env('PAYMOB_API_KEY'),
            'integration_id' => env('PAYMOB_INTEGRATION_ID'),
            'iframe_id'      => env('PAYMOB_IFRAME_ID'),
            'hmac_secret'    => env('PAYMOB_HMAC_SECRET'),
        ],
        'tamara' => [
            'api_token' => env('TAMARA_API_TOKEN'),
            'sandbox'   => env('TAMARA_SANDBOX', true),
        ],
        'mada' => [
            'secret_key' => env('MADA_SECRET_KEY'),
            'sandbox'    => env('MADA_SANDBOX', true),
        ],
    ],
];
```

---

## Developer Demo Routes

CyberCart ships with built-in sandbox routes so you can verify the entire e-commerce pipeline locally in seconds, without writing any code first. They are auto-registered by the service provider under the `cybercart` prefix and run on the `web` middleware group.

Start your local server (`php artisan serve`) and visit:

**`/cybercart/cart-demo`** — Cart logic & race condition check
Creates (or reuses) a demo product variant (`SKU: CYBER-DEMO-999`, 5 in stock), adds 2 units to the cart through `Cart::addToCart()`, and returns a JSON response with the resulting cart contents plus the status of the integrity safeguards (pessimistic row locking and race condition mitigation).

**`/cybercart/checkout-demo`** — Secure checkout & live SVG rendering
Runs the demo variant through `Checkout::execute()` against the `paymob` driver, then renders the variant's Code 128 barcode and a deep-linked QR code as inline SVG on the page so you can visually confirm the rendering engine without any external graphics dependency. If no demo variant exists yet, it redirects to `/cybercart/cart-demo` first.

These routes are intended for local development and sandbox verification only — they should not be exposed in a production environment.

---

## Usage

### 1. Bind a product variant

Attach the automatic barcode tracking trait to your product variant model:

```php
namespace Amreljako\CyberCart\Models;

use Illuminate\Database\Eloquent\Model;
use Amreljako\CyberCart\Traits\HasBarcode;

class ProductVariant extends Model
{
    use HasBarcode;
}
```

### 2. Cart operations

Interact with the cart through static facade methods, which are protected against client-side tampering:

```php
use Amreljako\CyberCart\Facades\Cart;

// Add an item, running pessimistic row-level validation checks
Cart::addToCart($productVariantId = 1, $quantity = 2);

// Retrieve verified cart contents, checked directly against the product table
$cart = Cart::getCartContent();

$subtotal = $cart['subtotal'];
$items = $cart['items'];
```

### 3. Run a secure checkout

Compile the order state, deduct inventory, lock the execution context, and generate a payment gateway redirect:

```php
use Amreljako\CyberCart\Facades\Checkout;

$shippingAddress = [
    'first_name' => 'Amr',
    'last_name'  => 'Elsayed',
    'email'      => 'amr.elsayed@example.com',
    'phone'      => '+201000000000',
    'address'    => 'Alexandria, Egypt',
];

try {
    $response = Checkout::execute($shippingAddress, $paymentDriver = 'paymob');

    // Redirect the customer to the provider's payment iframe
    return redirect()->away($response['redirect_url']);
} catch (\Exception $e) {
    return response()->json(['error' => $e->getMessage()], 422);
}
```

### 4. Render barcodes and QR codes

Display tracking identifiers without relying on external graphics extensions:

```php
{{-- Render a Code 128 barcode inline as SVG --}}
{!! $productVariant->renderBarcodeSvg() !!}

{{-- Render a QR code linking to the order's tracking page --}}
{!! $productVariant->renderQrCodeSvg('https://yourdomain.com/orders/' . $order->order_number) !!}
```

---

## License

The MIT License (MIT). See the LICENSE file for full terms.