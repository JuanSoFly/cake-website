<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'status',
        'subtotal',
        'tax',
        'total',
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_address',
        'delivery_date',
        'delivery_time',
        'special_instructions',
        'payment_method',
        'payment_status',
        'transaction_id'
    ];

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber()
    {
        $prefix = 'SD'; // Sweet Delights
        $timestamp = now()->format('YmdHis');
        $random = rand(100, 999);
        return $prefix . $timestamp . $random;
    }

    /**
     * Get the items for the order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
