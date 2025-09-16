<?php

namespace OpenCartBot\NBUQRGenerator\Tests;

use PHPUnit\Framework\TestCase;
use OpenCartBot\NBUQRGenerator\NBUQRCodeGenerator;
use DateTime;
use InvalidArgumentException;
use RuntimeException;

class NBUQRCodeGeneratorTest extends TestCase
{
    private $validOptions;
    
    protected function setUp(): void
    {
        $this->validOptions = [
            'function' => 'ICT',
            'recipient' => 'ТОВ "Тестова компанія"',
            'account' => 'UA123456789012345678901234567',
            'amount' => 100.50,
            'recipient_code' => '12345678',
            'category' => 'MP2B/GSCB',
            'reference' => 'REF-123',
            'purpose' => 'Тестовий платіж'
        ];
    }
    
    public function testConstructorWithValidOptions(): void
    {
        $generator = new NBUQRCodeGenerator($this->validOptions);
        $this->assertInstanceOf(NBUQRCodeGenerator::class, $generator);
        $this->assertEmpty($generator->getErrors());
    }
    
    public function testGenerateURLWithValidData(): void
    {
        $generator = new NBUQRCodeGenerator($this->validOptions);
        $url = $generator->generateURL();
        
        $this->assertIsString($url);
        $this->assertStringStartsWith('https://qr.bank.gov.ua/', $url);
        $this->assertLessThanOrEqual(507, strlen($url));
    }
    
    public function testValidationFailsWithEmptyRecipient(): void
    {
        $options = $this->validOptions;
        $options['recipient'] = '';
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Recipient is required', $errors);
    }
    
    public function testValidationFailsWithEmptyAccount(): void
    {
        $options = $this->validOptions;
        $options['account'] = '';
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Account is required', $errors);
    }
    
    public function testValidationFailsWithInvalidAccount(): void
    {
        $options = $this->validOptions;
        $options['account'] = 'INVALID_IBAN';
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Account must be exactly 29 characters', $errors);
    }
    
    public function testValidationFailsWithNonUkrainianIBAN(): void
    {
        $options = $this->validOptions;
        $options['account'] = 'DE123456789012345678901234567';
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Invalid Ukrainian IBAN format', $errors);
    }
    
    public function testValidationFailsWithEmptyRecipientCode(): void
    {
        $options = $this->validOptions;
        $options['recipient_code'] = '';
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Recipient code is required', $errors);
    }
    
    public function testValidationFailsWithEmptyPurpose(): void
    {
        $options = $this->validOptions;
        $options['purpose'] = '';
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Purpose is required', $errors);
    }
    
    public function testValidationFailsWithInvalidFunction(): void
    {
        $options = $this->validOptions;
        $options['function'] = 'INVALID';
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Invalid function', $errors);
    }
    
    public function testValidationFailsWithInvalidEncoding(): void
    {
        $options = $this->validOptions;
        $options['encoding'] = '3';
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Invalid encoding', $errors);
    }
    
    public function testValidationFailsWithNegativeAmount(): void
    {
        $options = $this->validOptions;
        $options['amount'] = -100;
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Amount must be between 0 and 999999999.99', $errors);
    }
    
    public function testValidationFailsWithTooLargeAmount(): void
    {
        $options = $this->validOptions;
        $options['amount'] = 1000000000;
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Amount must be between 0 and 999999999.99', $errors);
    }
    
    public function testValidationFailsWithNonUAHCurrency(): void
    {
        $options = $this->validOptions;
        $options['currency'] = 'USD';
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Only UAH currency is supported', $errors);
    }
    
    public function testValidationFailsWithTooLongRecipient(): void
    {
        $options = $this->validOptions;
        $options['recipient'] = str_repeat('А', 141);
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Recipient name too long (max 140 characters)', $errors);
    }
    
    public function testValidationFailsWithTooLongRecipientCode(): void
    {
        $options = $this->validOptions;
        $options['recipient_code'] = '12345678901';
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Recipient code too long (max 10 characters)', $errors);
    }
    
    public function testValidationFailsWithTooLongCategory(): void
    {
        $options = $this->validOptions;
        $options['category'] = '1234567890';
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Category too long (max 9 characters)', $errors);
    }
    
    public function testValidationFailsWithTooLongReference(): void
    {
        $options = $this->validOptions;
        $options['reference'] = str_repeat('A', 36);
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Reference too long (max 35 characters)', $errors);
    }
    
    public function testValidationFailsWithTooLongPurpose(): void
    {
        $options = $this->validOptions;
        $options['purpose'] = str_repeat('А', 421);
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Purpose too long (max 420 characters)', $errors);
    }
    
    public function testValidationFailsWithTooLongDisplay(): void
    {
        $options = $this->validOptions;
        $options['display'] = str_repeat('А', 141);
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Display text too long (max 140 characters)', $errors);
    }
    
    public function testValidationFailsWithInvalidLockMask(): void
    {
        $options = $this->validOptions;
        $options['lock_mask'] = 'INVALID';
        
        $generator = new NBUQRCodeGenerator($options);
        $errors = $generator->getErrors();
        
        $this->assertContains('Lock mask must be 4 hex characters', $errors);
    }
    
    public function testGenerateURLThrowsExceptionWhenValidationFails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Validation failed');
        
        $options = $this->validOptions;
        $options['recipient'] = '';
        
        $generator = new NBUQRCodeGenerator($options);
        $generator->generateURL();
    }
    
    public function testParseValidURL(): void
    {
        $generator = new NBUQRCodeGenerator($this->validOptions);
        $url = $generator->generateURL();
        
        $parsedData = $generator->parseURL($url);
        
        $this->assertEquals('BCD', $parsedData['service_tag']);
        $this->assertEquals('003', $parsedData['version']);
        $this->assertEquals('ICT', $parsedData['function']);
        $this->assertEquals('ТОВ "Тестова компанія"', $parsedData['recipient']);
        $this->assertEquals('UA123456789012345678901234567', $parsedData['account']);
    }
    
    public function testParseURLThrowsExceptionForInvalidURL(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid QR URL format');
        
        $generator = new NBUQRCodeGenerator($this->validOptions);
        $generator->parseURL('https://invalid.com/test');
    }
    
    public function testNullAmountGeneratesEmptyAmountField(): void
    {
        $options = $this->validOptions;
        $options['amount'] = null;
        
        $generator = new NBUQRCodeGenerator($options);
        $url = $generator->generateURL();
        $parsedData = $generator->parseURL($url);
        
        $this->assertEquals('', $parsedData['amount']);
    }
    
    public function testAmountFormatting(): void
    {
        $testCases = [
            100 => 'UAH100',
            100.5 => 'UAH100.5',
            100.50 => 'UAH100.5',
            100.99 => 'UAH100.99',
            0 => 'UAH0'
        ];
        
        foreach ($testCases as $input => $expected) {
            $options = $this->validOptions;
            $options['amount'] = $input;
            
            $generator = new NBUQRCodeGenerator($options);
            $url = $generator->generateURL();
            $parsedData = $generator->parseURL($url);
            
            $this->assertEquals($expected, $parsedData['amount'], "Failed for amount: $input");
        }
    }
    
    public function testDateTimeFormatting(): void
    {
        $dateTime = new DateTime('2025-09-21 12:30:45');
        
        $options = $this->validOptions;
        $options['valid_until'] = $dateTime;
        $options['created_at'] = $dateTime;
        
        $generator = new NBUQRCodeGenerator($options);
        $url = $generator->generateURL();
        $parsedData = $generator->parseURL($url);
        
        $this->assertEquals('250921123000', $parsedData['valid_until']);
        $this->assertEquals('250921123000', $parsedData['created_at']);
    }
    
    public function testSetOptionsOverridesDefaults(): void
    {
        $generator = new NBUQRCodeGenerator();
        
        $newOptions = $this->validOptions;
        $newOptions['function'] = 'UCT';
        $newOptions['encoding'] = '1';
        
        $generator->setOptions($newOptions);
        $options = $generator->getOptions();
        
        $this->assertEquals('UCT', $options['function']);
        $this->assertEquals('1', $options['encoding']);
    }
    
    public function testConstants(): void
    {
        $this->assertEquals('003', NBUQRCodeGenerator::VERSION);
        $this->assertEquals('BCD', NBUQRCodeGenerator::SERVICE_TAG);
        $this->assertEquals('UCT', NBUQRCodeGenerator::FUNCTION_UCT);
        $this->assertEquals('ICT', NBUQRCodeGenerator::FUNCTION_ICT);
        $this->assertEquals('XCT', NBUQRCodeGenerator::FUNCTION_XCT);
        $this->assertEquals('1', NBUQRCodeGenerator::ENCODING_UTF8);
        $this->assertEquals('2', NBUQRCodeGenerator::ENCODING_WIN1251);
    }
}