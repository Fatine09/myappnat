<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
        }
        .details {
            margin-bottom: 20px;
        }
        .details table {
            width: 100%;
            border-collapse: collapse;
        }
        .details th, .details td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .total {
            font-weight: bold;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Invoice</h1>
        <p>Order Number: {{ $order->order_number }}</p>
        <p>Date: {{ now()->format('d/m/Y') }}</p>
    </div>
    <div class="details">
        <h2>Order Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->price, 2) }} €</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="total">Total Amount: {{ number_format($order->total_amount, 2) }} €</p>
    </div>
</body>
</html>