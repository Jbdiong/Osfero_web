<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px; }
        .header { background: #f8f9fa; padding: 10px 20px; border-bottom: 2px solid #007bff; margin-bottom: 20px; }
        .footer { font-size: 0.8em; color: #777; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; }
        .meta { background: #fff9db; padding: 10px; border-radius: 4px; margin: 15px 0; border: 1px solid #ffe066; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Order Progress Update</h2>
        </div>
        
        <p>Dear Customer,</p>
        
        <p>{!! nl2br(e($content)) !!}</p>
        
        <div class="meta">
            <strong>Order Details:</strong><br>
            Order ID: #{{ $order->id }}<br>
            Invoice No: {{ $order->invoice_no ?? 'N/A' }}
        </div>

        <p>If you have any questions, please feel free to contact us.</p>
        
        <div class="footer">
            Best regards,<br>
            <strong>{{ $order->tenant?->name ?? config('app.name') }} Team</strong>
        </div>
    </div>
</body>
</html>
