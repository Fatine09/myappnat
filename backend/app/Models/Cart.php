<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id', // For guest users
        'total_amount',
        'item_count',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function calculateTotals()
    {
        $this->total_amount = $this->items->sum(function ($item) {
            return $item->quantity * $item->price;
        });
        
        $this->item_count = $this->items->sum('quantity');
        
        return $this;
    }
};