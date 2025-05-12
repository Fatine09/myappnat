<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $payment->payment_id }}</title>
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
        <h1>Receipt</h1>
        <p>Payment ID: {{ $payment->payment_id }}</p>
        <p>Date: {{ $payment->created_at->format('d/m/Y') }}</p>
    </div>
    <div class="details">
        <h2>Payment Details</h2>
        <table>
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $order->order_number }}</td>
                    <td>{{ number_format($payment->amount, 2) }} €</td>
                    <td>{{ ucfirst($payment->status) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>