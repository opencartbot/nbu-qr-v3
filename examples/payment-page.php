<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оплата замовлення</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #cccccc;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .payment-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        .order-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .order-info h2 {
            color: #495057;
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .order-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 4px 0;
        }
        
        .order-detail:last-child {
            border-top: 1px solid #dee2e6;
            margin-top: 10px;
            padding-top: 10px;
            font-weight: bold;
            font-size: 18px;
        }
        
        .qr-section {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .qr-section h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .qr-code {
            margin: 20px 0;
        }

        .qr-code img {
            width: 100%;
            max-width: 280px;
            border-radius: 8px;
        }
        
        .qr-instructions {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.5;
            margin-top: 20px;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="logo">
            <h1>Інтернет-магазин</h1>
            <p>Оплата замовлення</p>
        </div>

        <?php
        require_once 'vendor/autoload.php';
        
        use OpenCartBot\NBUQRGenerator\NBUQRCodeGenerator;
        
        // Дані замовлення (зазвичай з бази даних)
        $order = [
            'id' => '821558965',
            'items' => [
                ['name' => 'Товар 1', 'price' => 100],
                ['name' => 'Товар 2', 'price' => 50]
            ],
            'total' => 150,
            'customer' => [
                'name' => 'Іван Петренко',
                'email' => 'ivan@example.com'
            ]
        ];
        
        $error = '';
        $qrCodeSVG = '';
        
        // Генерація QR-коду
        try {
            // Налаштування QR-коду для інтернет-магазину
            $qrOptions = [
                'function' => NBUQRCodeGenerator::FUNCTION_ICT,
                'recipient' => 'ТОВ ТЕСТ»',
                'account' => 'UA673005280000026500504354011',
                'amount' => $order['total'],
                'recipient_code' => '37193011',
                'category' => 'OTHR/GDDS',
                'reference' => $order['id'],
                'purpose' => '?MerchantBusinessName="ІНТЕРНЕТ-МАГАЗИН", Покупка товарів, замовлення №' . $order['id'] . '.',
                'display' => '?<InstrForCdtrAgt><InstrInf>MerchID:01234-TermId:43210</InstrInf></InstrForCdtrAgt>',
                'lock_mask' => 'FFFF',
                'valid_until' => new DateTime('+24 hours'),
                'created_at' => new DateTime()
            ];
            
            $generator = new NBUQRCodeGenerator($qrOptions);
            $qrUrl = $generator->generateURL();
            $qrCodeSVG = $generator->generateQRCodeDataURI();
            
        } catch (Exception $e) {
            $error = 'Помилка генерації QR-коду: ' . $e->getMessage();
        }
        ?>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="order-info">
            <h2>Деталі замовлення #<?= $order['id'] ?></h2>
            <?php foreach ($order['items'] as $item): ?>
                <div class="order-detail">
                    <span><?= htmlspecialchars($item['name']) ?></span>
                    <span><?= number_format($item['price'], 2) ?> ₴</span>
                </div>
            <?php endforeach; ?>
            <div class="order-detail">
                <span>До сплати:</span>
                <span><?= number_format($order['total'], 2) ?> ₴</span>
            </div>
        </div>

        <?php if ($qrCodeSVG): ?>
            <div class="qr-section">
                <h3>Оплата через мобільний банкінг</h3>
                <div class="qr-code">
                   <img src="<?php echo $qrCodeSVG ?>" alt="QR код для оплати"/>
                </div>
                <div class="qr-instructions">
                    <p><strong>Як сплатити:</strong></p>
                    <p>1. Відкрийте додаток вашого банку</p>
                    <p>2. Відкрийте QR сканер в додатку</p>
                    <p>2. Наведіть камеру на QR-код</p>
                    <p>3. Перевірте дані та підтвердіть платіж</p>
                    <p><small>QR-код дійсний 24 години</small></p>
                </div>
            </div>
        <?php endif; ?>

        <a href="<?= $qrUrl ?>" class="btn">
            Оплатити <?= number_format($order['total'], 2) ?> ₴
        </a>
    </div>
</body>
</html>