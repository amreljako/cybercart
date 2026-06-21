<?php

namespace Amreljako\CyberCart\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    // Explicitly define the table name specified in migrations
    protected $table = 'store_products';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'is_active',
        'meta_tags',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta_tags' => 'array', // Dynamic SEO array mapping
    ];

    /**
     * Get all customizable variations for this product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }
}