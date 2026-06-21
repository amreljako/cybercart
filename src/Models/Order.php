<?php

namespace Amreljako\CyberCart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Order extends Model
{
    protected $table = 'store_orders';

    protected $fillable = [
        'order_number',
        'customer_type',
        'customer_id',
        'subtotal',
        'discount_total',
        'coupon_code',
        'shipping_total',
        'grand_total',
        'payment_driver',
        'payment_status',
        'payment_transaction_id',
        'order_status',
        'shipping_address',
        'billing_address',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'shipping_address' => 'array',
        'billing_address' => 'array',
    ];

    /**
     * Seamless Polymorphic relation to bind with any host application user architecture.
     */
    public function customer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all individual line item snapshots attached to this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}