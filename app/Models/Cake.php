<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cake extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'is_featured'
    ];

    /**
     * Get the formatted price with currency symbol.
     */
    public function getFormattedPriceAttribute()
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get the orders that contain this cake.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
