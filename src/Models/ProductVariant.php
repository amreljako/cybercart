<?php

namespace Amreljako\CyberCart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Amreljako\CyberCart\Traits\HasBarcode;

class ProductVariant extends Model
{
    use HasBarcode;

    protected $table = 'store_product_variants';

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'option_values',
        'price',
        'compare_at_price',
        'stock_quantity',
        'track_stock',
    ];

    protected $casts = [
        'option_values' => 'array', // e.g., {"color": "black", "size": "XL"}
        'price' => 'decimal:2',     // Secure float precision tampering mitigation
        'compare_at_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'track_stock' => 'boolean',
    ];

    /**
     * Get the parent product configuration.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}