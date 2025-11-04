<?php

namespace OpenCartBot\NBUQRGenerator;

use DateTime;
use InvalidArgumentException;
use RuntimeException;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QR Code Generator для стандарту НБУ версії формату 003
 */
class NBUQRCodeGenerator
{
    const VERSION = '003';
    const SERVICE_TAG = 'BCD';
    const DEFAULT_BASE_URL = 'https://qr.bank.gov.ua/';
    const MAX_DATA_SIZE = 507;
    
    const FUNCTION_UCT = 'UCT';
    const FUNCTION_ICT = 'ICT';
    const FUNCTION_XCT = 'XCT';
    
    const ENCODING_UTF8 = '1';
    const ENCODING_WIN1251 = '2';
    
    private $baseUrl;
    private $options;
    private $errors = [];
    
    public function __construct(array $options = [], string $baseUrl = self::DEFAULT_BASE_URL)
    {
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
        $this->setOptions($options);
    }
    
    public function setOptions(array $options): self
    {
        $defaults = [
            'function' => self::FUNCTION_ICT,
            'encoding' => self::ENCODING_WIN1251,
            'recipient' => '',
            'account' => '',
            'amount' => null,
            'currency' => 'UAH',
            'recipient_code' => '',
            'category' => '',
            'reference' => '',
            'purpose' => '',
            'display' => '',
            'lock_mask' => '',
            'valid_until' => null,
            'created_at' => null
        ];
        
        $this->options = array_merge($defaults, $options);
        $this->validateOptions();
        
        return $this;
    }
    
    private function validateOptions(): void
    {
        $this->errors = [];
        
        // Валідація функції
        $validFunctions = [self::FUNCTION_UCT, self::FUNCTION_ICT, self::FUNCTION_XCT];
        if (!in_array($this->options['function'], $validFunctions)) {
            $this->errors[] = 'Invalid function';
        }
        
        // Валідація кодування
        $validEncodings = [self::ENCODING_UTF8, self::ENCODING_WIN1251];
        if (!in_array($this->options['encoding'], $validEncodings)) {
            $this->errors[] = 'Invalid encoding';
        }
        
        // Валідація отримувача
        if (empty($this->options['recipient'])) {
            $this->errors[] = 'Recipient is required';
        } elseif (mb_strlen($this->options['recipient']) > 140) {
            $this->errors[] = 'Recipient name too long (max 140 characters)';
        }
        
        // Валідація рахунку
        if (empty($this->options['account'])) {
            $this->errors[] = 'Account is required';
        } else {
            $account = preg_replace('/\s+/', '', $this->options['account']);
            if (strlen($account) !== 29) {
                $this->errors[] = 'Account must be exactly 29 characters';
            } elseif (!preg_match('/^UA\d{27}$/', $account)) {
                $this->errors[] = 'Invalid Ukrainian IBAN format';
            }
        }
        
        // Валідація суми
        if ($this->options['amount'] !== null) {
            if (!is_numeric($this->options['amount']) || $this->options['amount'] < 0 || $this->options['amount'] > 999999999.99) {
                $this->errors[] = 'Amount must be between 0 and 999999999.99';
            }
            if ($this->options['currency'] !== 'UAH') {
                $this->errors[] = 'Only UAH currency is supported';
            }
        }
        
        // Валідація коду отримувача
        if (empty($this->options['recipient_code'])) {
            $this->errors[] = 'Recipient code is required';
        } elseif (mb_strlen($this->options['recipient_code']) > 10) {
            $this->errors[] = 'Recipient code too long (max 10 characters)';
        }
        
        // Валідація категорії
        if (!empty($this->options['category']) && mb_strlen($this->options['category']) > 9) {
            $this->errors[] = 'Category too long (max 9 characters)';
        }
        
        // Валідація reference
        if (!empty($this->options['reference']) && mb_strlen($this->options['reference']) > 35) {
            $this->errors[] = 'Reference too long (max 35 characters)';
        }
        
        // Валідація призначення
        if (empty($this->options['purpose'])) {
            $this->errors[] = 'Purpose is required';
        } elseif (mb_strlen($this->options['purpose']) > 420) {
            $this->errors[] = 'Purpose too long (max 420 characters)';
        }
        
        // Валідація display
        if (!empty($this->options['display']) && mb_strlen($this->options['display']) > 140) {
            $this->errors[] = 'Display text too long (max 140 characters)';
        }
        
        // Валідація lock_mask
        if (!empty($this->options['lock_mask']) && !preg_match('/^[0-9A-F]{4}$/', $this->options['lock_mask'])) {
            $this->errors[] = 'Lock mask must be 4 hex characters';
        }
    }
    
    private function formatAmount(): string
    {
        if ($this->options['amount'] === null) {
            return '';
        }
        
        $formatted = rtrim(rtrim(number_format($this->options['amount'], 2, '.', ''), '0'), '.');
        return $this->options['currency'] . $formatted;
    }
    
    private function formatDateTime(DateTime $dateTime = null): string
    {
        if (!$dateTime) {
            return '';
        }
        return $dateTime->format('ymjHi') . '00';
    }
    
    private function buildQRData(): string
    {
        $parts = [];
        
        $parts[] = self::SERVICE_TAG;
        $parts[] = self::VERSION;
        $parts[] = $this->options['encoding'];
        $parts[] = $this->options['function'];
        $parts[] = ''; // unique_id - RFU
        $parts[] = $this->options['recipient'];
        $parts[] = preg_replace('/\s+/', '', $this->options['account']);
        $parts[] = $this->formatAmount();
        $parts[] = $this->options['recipient_code'];
        $parts[] = $this->options['category'];
        $parts[] = $this->options['reference'];
        $parts[] = $this->options['purpose'];
        $parts[] = $this->options['display'];
        $parts[] = $this->options['lock_mask'];
        $parts[] = $this->formatDateTime($this->options['valid_until']);
        $parts[] = $this->formatDateTime($this->options['created_at']);
        $parts[] = ''; // signature - RFU
        
        return implode("\n", $parts);
    }
    
    public function generateURL(): string
    {
        if (!empty($this->errors)) {
            throw new RuntimeException('Validation failed: ' . implode(', ', $this->errors));
        }
        
        $qrData = $this->buildQRData();
        $encodedData = $this->base64UrlEncode($qrData);
        
        $url = $this->baseUrl . $encodedData;
        
        if (strlen($url) > self::MAX_DATA_SIZE) {
            throw new RuntimeException('QR data size exceeds maximum allowed');
        }
        
        return $url;
    }
    
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    public function parseURL(string $url): array
    {
        if (substr($url, 0, strlen($this->baseUrl)) !== $this->baseUrl) {
            throw new InvalidArgumentException('Invalid QR URL format');
        }
        
        $encodedData = substr($url, strlen($this->baseUrl));
        $decodedData = $this->base64UrlDecode($encodedData);
        $parts = explode("\n", $decodedData);
        
        if (count($parts) < 17) {
            throw new InvalidArgumentException('Invalid QR data structure');
        }
        
        return [
            'service_tag' => $parts[0] ?? '',
            'version' => $parts[1] ?? '',
            'encoding' => $parts[2] ?? '',
            'function' => $parts[3] ?? '',
            'unique_id' => $parts[4] ?? '',
            'recipient' => $parts[5] ?? '',
            'account' => $parts[6] ?? '',
            'amount' => $parts[7] ?? '',
            'recipient_code' => $parts[8] ?? '',
            'category' => $parts[9] ?? '',
            'reference' => $parts[10] ?? '',
            'purpose' => $parts[11] ?? '',
            'display' => $parts[12] ?? '',
            'lock_mask' => $parts[13] ?? '',
            'valid_until' => $parts[14] ?? '',
            'created_at' => $parts[15] ?? '',
            'signature' => $parts[16] ?? ''
        ];
    }
    
    public function generateQRCode(array $options = []): string
    {
        $url = $this->generateURL();

        $defaultOptions = [
            'version'      => QRCode::VERSION_AUTO,
            'outputType'   => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel'     => QRCode::ECC_M, // Рівень корекції помилок M як вимагає НБУ
            'scale'        => 8,
            'addQuietzone' => true,
            'cssClass'     => 'nbu-qr-code',
            'svgViewBoxSize' => 500,
        ];

        $qrOptions = new QROptions(array_merge($defaultOptions, $options));

        $qrCode = new QRCode($qrOptions);

        return $qrCode->render($url);
    }
    
    public function getOptions(): array
    {
        return $this->options;
    }
    
    public function generateQRCodePNG(array $options = []): string
    {
        $url = $this->generateURL();

        $defaultOptions = [
            'version'      => QRCode::VERSION_AUTO,
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => QRCode::ECC_M,
            'scale'        => 8,
            'addQuietzone' => true,
            'imageBase64'  => false,
        ];

        $qrOptions = new QROptions(array_merge($defaultOptions, $options));

        $qrCode = new QRCode($qrOptions);

        return $qrCode->render($url);
    }
    
    public function generateQRCodeDataURI(array $options = []): string
    {
        $url = $this->generateURL();

        $defaultOptions = [
            'version'      => QRCode::VERSION_AUTO,
            'outputType'   => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => QRCode::ECC_M,
            'scale'        => 8,
            'addQuietzone' => true,
            'imageBase64'  => true, // Data URI формат
        ];

        $qrOptions = new QROptions(array_merge($defaultOptions, $options));

        $qrCode = new QRCode($qrOptions);

        return $qrCode->render($url);
    }
}