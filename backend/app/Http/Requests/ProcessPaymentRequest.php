<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'payment_method' => 'required|string|in:credit_card,paypal',
            'payment_details' => 'required|array',
        ];
    }
};