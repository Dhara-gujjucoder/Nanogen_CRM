<?php

namespace App\Models;
use App\Models\OrderManagementProduct;

use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    protected $table = 'product_variations';
    protected $guarded = [];


    public function variation_option_value()
    {
        return $this->hasOne(VariationOption::class, 'id', 'variation_option_id');
    }

    // public function orderManagementProducts()
    // {
    //     return $this->hasMany(OrderManagementProduct::class, 'packing_size_id', 'variation_option_id');
    // }


    /**
     * Get all of the comments for the ProductVariation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    // public function comments(): HasMany
    // {
    //     return $this->hasMany(VariationOption::class, 'foreign_key', 'local_key');
    // }
}
