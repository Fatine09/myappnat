<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'due_date' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function generateInvoiceNumber()
    {
        return 'INV-' . strtoupper(uniqid());
    }
};