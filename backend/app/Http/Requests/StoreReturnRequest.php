<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReturnRequest extends FormRequest
{
    public function rules()
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'reason' => 'required|string',
            'items' => 'required|array',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'nullable|string',
        ];
    }
};