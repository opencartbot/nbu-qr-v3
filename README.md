# NBU QR Code Generator v3

PHP бібліотека для генерації QR-кодів згідно зі стандартом Національного банку України версії формату 003.

## Опис

Бібліотека дозволяє створювати QR-коди для кредитових та миттєвих кредитових переказів відповідно до документу НБУ "[Правила формування, передачі та обробки структури даних і графічного зображення QR-коду для обміну реквізитами кредитових та миттєвих кредитових переказів](https://bank.gov.ua/admin_uploads/law/19082025_97.pdf?v=14)".

## Встановлення

```bash
composer require opencartbot/nbu-qr-v3
```

## Використання

### Базове використання

```php
<?php
require_once 'vendor/autoload.php';

use OpenCartBot\NBUQRGenerator\NBUQRCodeGenerator;

$options = [
    'function' => 'ICT',
    'recipient' => 'ТОВ "Інтернет магазин"',
    'account' => 'UA123456789012345678901234567',
    'amount' => 150.75,
    'recipient_code' => '12345678',
    'category' => 'MP2B/GSCB',
    'reference' => 'ORDER-12345',
    'purpose' => 'Оплата товарів по замовленню №12345'
];

$qr = new NBUQRCodeGenerator($options);
$qrUrl = $qr->generateURL();

echo $qrUrl;
```

### Генерація QR-коду (SVG)

```php
use OpenCartBot\NBUQRGenerator\NBUQRCodeGenerator;

$options = [
    'recipient' => 'ТОВ "Магазин"',
    'account' => 'UA123456789012345678901234567',
    'amount' => 150.75,
    'recipient_code' => '12345678',
    'purpose' => 'Оплата товарів'
];

$qr = new NBUQRCodeGenerator($options);

// Отримати URL для QR-коду
$qrUrl = $qr->generateURL();

// Згенерувати SVG QR-код
$svgQRCode = $qr->generateQRCode();
echo $svgQRCode;

// Згенерувати PNG QR-код (бінарні дані)
$pngQRCode = $qr->generateQRCodePNG();
file_put_contents('qrcode.png', $pngQRCode);

// Згенерувати QR-код як Data URI (base64)
$dataURI = $qr->generateQRCodeDataURI();
echo '<img src="' . $dataURI . '" alt="QR Code">';
```

## Параметри

### Обов'язкові параметри

| Параметр | Тип | Опис | Максимальна довжина |
|----------|-----|------|---------------------|
| `recipient` | string | Прізвище, ім'я, по батькові фізичної особи або найменування юридичної особи | 140 символів |
| `account` | string | Номер рахунку отримувача (IBAN) | 29 символів |
| `recipient_code` | string | Код отримувача коштів | 10 символів |
| `purpose` | string | Призначення платежу | 420 символів |

### Опціональні параметри

| Параметр | Тип | Опис | Значення за замовчуванням |
|----------|-----|------|---------------------------|
| `function` | string | Функція платежу (UCT/ICT/XCT) | ICT |
| `encoding` | string | Кодування (1 - UTF-8, 2 - Win1251) | 2 |
| `amount` | float | Сума платежу | null |
| `currency` | string | Валюта | UAH |
| `category` | string | Категорія/ціль платежу | '' |
| `reference` | string | Ідентифікатор рахунку на оплату | '' |
| `display` | string | Додатковий текст для відображення | '' |
| `lock_mask` | string | Маска заборони зміни полів (4 hex символи) | '' |
| `valid_until` | DateTime | Дата/час дії рахунку | null |
| `created_at` | DateTime | Дата/час створення | null |

## Константи

### Функції платежу
- `NBUQRCodeGenerator::FUNCTION_UCT` - Кредитовий переказ
- `NBUQRCodeGenerator::FUNCTION_ICT` - Миттєвий кредитовий переказ
- `NBUQRCodeGenerator::FUNCTION_XCT` - Миттєвий або кредитовий переказ

### Кодування
- `NBUQRCodeGenerator::ENCODING_UTF8` - UTF-8
- `NBUQRCodeGenerator::ENCODING_WIN1251` - Windows-1251

## Приклади використання

### Комунальний платіж

```php
$options = [
    'function' => NBUQRCodeGenerator::FUNCTION_UCT,
    'recipient' => 'ТОВ "ГК Нафтогаз України"',
    'account' => 'UA201234560000000260323012042',
    'amount' => 2998.39,
    'recipient_code' => '40121452',
    'category' => 'SUPP/SUPP',
    'reference' => 'AA15678-679',
    'purpose' => 'Оплата за газ, особовий рахунок 123456789',
    'lock_mask' => 'FEFF'
];

$qr = new NBUQRCodeGenerator($options);
echo $qr->generateURL();
```

### P2P переказ

```php
$options = [
    'function' => NBUQRCodeGenerator::FUNCTION_ICT,
    'recipient' => 'Петренко Роман Петрович',
    'account' => 'UA906543210000000260323012024',
    'amount' => 63,
    'recipient_code' => '40121425',
    'category' => 'MP2P/MP2B',
    'reference' => 'DR-5678-12',
    'purpose' => 'За каву'
];

$qr = new NBUQRCodeGenerator($options);
echo $qr->generateURL();
```

### Оплата в магазині

```php
$options = [
    'function' => NBUQRCodeGenerator::FUNCTION_ICT,
    'recipient' => 'ТОВ "Сільпо-Фуд"',
    'account' => 'UA133071230000026006010423515',
    'amount' => 111,
    'recipient_code' => '40720198',
    'category' => 'MP2B/GSCB',
    'reference' => '№148/720/501',
    'purpose' => 'Покупка товарів, №148/720/501',
    'lock_mask' => 'FFFF'
];

$qr = new NBUQRCodeGenerator($options);
echo $qr->generateURL();
```

### Оплата в інтернет-магазині

```php
$options = [
    'function' => NBUQRCodeGenerator::FUNCTION_ICT,
    'recipient' => 'ТОВ "ФК ЕВО"',
    'account' => 'UA673005280000026500504354077',
    'amount' => 150,
    'recipient_code' => '37193071',
    'category' => 'OTHR/GDDS',
    'reference' => '1225102576',
    'purpose' => '?MerchantBusinessName="ROZETKA.UA", Покупка товарів, замовлення №821558965.',
    'display' => '?<InstrForCdtrAgt><InstrInf>MerchID:01234-TermId:43210</InstrInf></InstrForCdtrAgt>',
    'lock_mask' => 'FFFF', // Заборонити редагування всіх полів
    'valid_until' => new DateTime('2025-03-21 12:00:00'),
    'created_at' => new DateTime('2025-01-29 12:00:00')
];

$qr = new NBUQRCodeGenerator($options);
$qrUrl = $qr->generateURL();
echo $qrUrl;

$qrCodeSVG = $qr->generateQRCode();
echo '<img src="' . $qrCodeSVG . '" alt="QR Code">';
```

### Встановлення дат

```php
$validUntil = new DateTime('2025-09-21 12:00:00');
$createdAt = new DateTime();

$options = [
    'recipient' => 'ТОВ "Компанія"',
    'account' => 'UA123456789012345678901234567',
    'amount' => 1000,
    'recipient_code' => '12345678',
    'purpose' => 'Оплата послуг',
    'valid_until' => $validUntil,
    'created_at' => $createdAt
];

$qr = new NBUQRCodeGenerator($options);
echo $qr->generateURL();
```

## Парсинг QR URL

```php
$qr = new NBUQRCodeGenerator();
$url = 'https://qr.bank.gov.ua/QkNECjAwMwo...';

try {
    $data = $qr->parseURL($url);
    print_r($data);
} catch (Exception $e) {
    echo 'Помилка: ' . $e->getMessage();
}
```

## Обробка помилок

```php
$options = [
    'recipient' => '', // Помилка - порожнє поле
    'account' => 'UA123', // Помилка - неправильний формат
    'amount' => -100, // Помилка - від'ємна сума
];

$qr = new NBUQRCodeGenerator($options);

if ($errors = $qr->getErrors()) {
    echo "Помилки валідації:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
} else {
    echo $qr->generateURL();
}
```

## Вимоги

- PHP 7.4 або вище
- Розширення mbstring для роботи з Unicode

## Ліцензія

MIT License

## Підтримка

При виникненні проблем або запитань створюйте Issue в репозиторії GitHub.

## Додаткова інформація

Детальну інформацію про стандарт НБУ версії формату 003 можна знайти в офіційній документації Національного банку України:

- [Правила формування QR-коду (PDF)](https://bank.gov.ua/admin_uploads/law/19082025_97.pdf?v=14)
- [Офіційний сайт НБУ](https://bank.gov.ua)