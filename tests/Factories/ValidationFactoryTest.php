<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities\Tests\Factories;

use PHPUnit\Framework\TestCase;
use WebsiteSQL\Utilities\Factories\ValidationFactory;

class ValidationFactoryTest extends TestCase
{
    private ValidationFactory $validationFactory;
    
    protected function setUp(): void
    {
        $this->validationFactory = new ValidationFactory();
    }
    
    public function testDateWithValidDate(): void
    {
        $this->assertTrue($this->validationFactory->date('04/14/2025'));
        $this->assertFalse($this->validationFactory->hasErrors());
        $this->assertEmpty($this->validationFactory->getErrors());
    }
    
    public function testDateWithInvalidDate(): void
    {
        $this->assertFalse($this->validationFactory->date('13/40/2025'));
        $this->assertTrue($this->validationFactory->hasErrors());
        $this->assertArrayHasKey('date', $this->validationFactory->getErrors());
        $this->assertEquals('Invalid date format', $this->validationFactory->getErrors()['date']);
    }
    
    public function testDateWithCustomFormat(): void
    {
        $this->assertTrue($this->validationFactory->date('2025-04-14', 'Y-m-d'));
        $this->assertFalse($this->validationFactory->hasErrors());
        
        $this->assertFalse($this->validationFactory->date('04/14/2025', 'Y-m-d'));
        $this->assertTrue($this->validationFactory->hasErrors());
    }
    
    public function testDateWithNonStrictMode(): void
    {
        // In non-strict mode, this will pass as PHP tries to interpret the date
        $this->assertTrue($this->validationFactory->date('13/40/2025', 'm/d/Y', false));
        $this->assertFalse($this->validationFactory->hasErrors());
    }
    
    public function testEmailWithValidEmail(): void
    {
        $this->assertTrue($this->validationFactory->email('test@example.com'));
        $this->assertFalse($this->validationFactory->hasErrors());
    }
    
    public function testEmailWithInvalidEmail(): void
    {
        $this->assertFalse($this->validationFactory->email('invalid-email'));
        $this->assertTrue($this->validationFactory->hasErrors());
        $this->assertArrayHasKey('email', $this->validationFactory->getErrors());
        $this->assertEquals('Invalid email address', $this->validationFactory->getErrors()['email']);
    }
    
    public function testUrlWithValidUrl(): void
    {
        $this->assertTrue($this->validationFactory->url('https://www.example.com'));
        $this->assertFalse($this->validationFactory->hasErrors());
    }
    
    public function testUrlWithInvalidUrl(): void
    {
        $this->assertFalse($this->validationFactory->url('invalid-url'));
        $this->assertTrue($this->validationFactory->hasErrors());
        $this->assertArrayHasKey('url', $this->validationFactory->getErrors());
        $this->assertEquals('Invalid URL', $this->validationFactory->getErrors()['url']);
    }
    
    public function testIpWithValidIpv4(): void
    {
        $this->assertTrue($this->validationFactory->ip('192.168.1.1'));
        $this->assertFalse($this->validationFactory->hasErrors());
    }
    
    public function testIpWithValidIpv6(): void
    {
        $this->assertTrue($this->validationFactory->ip('2001:0db8:85a3:0000:0000:8a2e:0370:7334'));
        $this->assertFalse($this->validationFactory->hasErrors());
    }
    
    public function testIpWithInvalidIp(): void
    {
        $this->assertFalse($this->validationFactory->ip('256.256.256.256'));
        $this->assertTrue($this->validationFactory->hasErrors());
        $this->assertArrayHasKey('ip', $this->validationFactory->getErrors());
        $this->assertEquals('Invalid IP address', $this->validationFactory->getErrors()['ip']);
    }
    
    public function testRequiredWithNonEmptyValue(): void
    {
        $this->assertTrue($this->validationFactory->required('test value', 'testField'));
        $this->assertFalse($this->validationFactory->hasErrors());
    }
    
    public function testRequiredWithEmptyValue(): void
    {
        $this->assertFalse($this->validationFactory->required('', 'testField'));
        $this->assertTrue($this->validationFactory->hasErrors());
        $this->assertArrayHasKey('testField', $this->validationFactory->getErrors());
        $this->assertEquals('This field is required', $this->validationFactory->getErrors()['testField']);
    }
    
    public function testRequiredWithWhitespaceValue(): void
    {
        $this->assertFalse($this->validationFactory->required('   ', 'testField'));
        $this->assertTrue($this->validationFactory->hasErrors());
        $this->assertArrayHasKey('testField', $this->validationFactory->getErrors());
    }
    
    public function testMinLengthWithValidLength(): void
    {
        $this->assertTrue($this->validationFactory->minLength('test123', 5, 'testField'));
        $this->assertFalse($this->validationFactory->hasErrors());
    }
    
    public function testMinLengthWithInvalidLength(): void
    {
        $this->assertFalse($this->validationFactory->minLength('test', 5, 'testField'));
        $this->assertTrue($this->validationFactory->hasErrors());
        $this->assertArrayHasKey('testField', $this->validationFactory->getErrors());
        $this->assertEquals('Minimum length is 5 characters', $this->validationFactory->getErrors()['testField']);
    }
    
    public function testMaxLengthWithValidLength(): void
    {
        $this->assertTrue($this->validationFactory->maxLength('test', 10, 'testField'));
        $this->assertFalse($this->validationFactory->hasErrors());
    }
    
    public function testMaxLengthWithInvalidLength(): void
    {
        $this->assertFalse($this->validationFactory->maxLength('thisisatoolongvalue', 10, 'testField'));
        $this->assertTrue($this->validationFactory->hasErrors());
        $this->assertArrayHasKey('testField', $this->validationFactory->getErrors());
        $this->assertEquals('Maximum length is 10 characters', $this->validationFactory->getErrors()['testField']);
    }
    
    public function testNumericWithValidNumber(): void
    {
        $this->assertTrue($this->validationFactory->numeric('123', 'testField'));
        $this->assertFalse($this->validationFactory->hasErrors());
        
        $this->assertTrue($this->validationFactory->numeric('123.45', 'testField'));
        $this->assertFalse($this->validationFactory->hasErrors());
    }
    
    public function testNumericWithInvalidNumber(): void
    {
        $this->assertFalse($this->validationFactory->numeric('abc', 'testField'));
        $this->assertTrue($this->validationFactory->hasErrors());
        $this->assertArrayHasKey('testField', $this->validationFactory->getErrors());
        $this->assertEquals('Value must be numeric', $this->validationFactory->getErrors()['testField']);
    }
    
    public function testClearErrors(): void
    {
        // Generate some errors
        $this->validationFactory->email('invalid-email');
        $this->validationFactory->url('invalid-url');
        
        $this->assertTrue($this->validationFactory->hasErrors());
        $this->assertCount(2, $this->validationFactory->getErrors());
        
        // Clear errors
        $this->validationFactory->clearErrors();
        
        $this->assertFalse($this->validationFactory->hasErrors());
        $this->assertEmpty($this->validationFactory->getErrors());
    }
    
    public function testMultipleValidations(): void
    {
        $isValid = $this->validationFactory->required('test', 'field1');
        $isValid = $this->validationFactory->email('invalid-email') && $isValid;
        $isValid = $this->validationFactory->url('https://example.com') && $isValid;
        
        $this->assertFalse($isValid); // Should be false because email validation failed
        $this->assertTrue($this->validationFactory->hasErrors());
        $this->assertArrayHasKey('email', $this->validationFactory->getErrors());
        $this->assertCount(1, $this->validationFactory->getErrors());
    }
}