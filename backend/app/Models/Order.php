<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'total_amount',
        'payment_method',
        'shipping_address',
        'billing_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function return()
    {
        return $this->hasOne(ReturnRequest::class);
    }

    public function generateOrderNumber()
    {
        return 'ORD-' . strtoupper(uniqid());
    }
    public function invoice()
{
    return $this->hasOne(Invoice::class);
}

public function reviews()
{
    return $this->hasMany(Review::class);
}
};