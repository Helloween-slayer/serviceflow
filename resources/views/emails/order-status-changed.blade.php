<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статус заявки змінено</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .header {
            background: #2563eb;
            padding: 30px 40px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }
        .content {
            padding: 40px;
            color: #1f2937;
        }
        .status-box {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .status-old {
            color: #ef4444;
            font-weight: 600;
        }
        .status-new {
            color: #22c55e;
            font-weight: 600;
        }
        .button {
            display: inline-block;
            background: #2563eb;
            color: #ffffff !important;
            padding: 12px 28px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin: 20px 0 10px;
        }
        .button:hover {
            background: #1d4ed8;
        }
        .footer {
            padding: 20px 40px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 600;
        }
        .badge-old {
            background: #fee2e2;
            color: #dc2626;
        }
        .badge-new {
            background: #dcfce7;
            color: #16a34a;
        }
        .order-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        .arrow {
            font-size: 20px;
            margin: 0 8px;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Шапка -->
    <div class="header">
        <h1>📋 ServiceFlow</h1>
    </div>

    <!-- Контент -->
    <div class="content">
        <h2 style="margin-top: 0;">Вітаємо!</h2>

        <p style="font-size: 16px; line-height: 1.6;">
            Статус вашої заявки <strong>"{{ $order->title }}"</strong> було змінено.
        </p>

        <!-- Статусы -->
        <div class="status-box">
            <p style="margin: 0; text-align: center; font-size: 16px;">
                <span class="badge badge-old">{{ $oldStatus }}</span>
                <span class="arrow">➜</span>
                <span class="badge badge-new">{{ $newStatus }}</span>
            </p>
        </div>

        <!-- Инфо -->
        <div style="margin: 20px 0; font-size: 14px; color: #4b5563;">
            <p><strong>💰 Ціна:</strong> {{ $order->price }} ₴</p>
            <p><strong>📅 Створено:</strong> {{ $order->created_at->format('d.m.Y H:i') }}</p>
            @if($order->deadline)
                <p><strong>⏰ Дедлайн:</strong> {{ \Carbon\Carbon::parse($order->deadline)->format('d.m.Y') }}</p>
            @endif
        </div>

        <!-- Кнопка -->
        <div style="text-align: center;">
            <a href="{{ route('orders.show', $order->id) }}" class="button">🔗 Переглянути заявку</a>
        </div>
    </div>

    <!-- Футер -->
    <div class="footer">
        <p style="margin: 0;">
            Цей лист надіслано автоматично. Будь ласка, не відповідайте на нього.
        </p>
        <p style="margin: 5px 0 0;">
            &copy; {{ date('Y') }} <a href="{{ url('/') }}">ServiceFlow</a>
        </p>
    </div>
</div>
</body>
</html>
