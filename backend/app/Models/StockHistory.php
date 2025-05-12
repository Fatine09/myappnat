<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'previous_stock',
        'new_stock',
        'adjustment',
        'type', // 'purchase', 'sale', 'return', 'manual_adjustment'
        'reference_id', // Could be order_id, return_id
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
};