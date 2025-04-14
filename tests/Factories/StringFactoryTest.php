<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities\Tests\Factories;

use PHPUnit\Framework\TestCase;
use WebsiteSQL\Utilities\Factories\StringFactory;
use Exception;

class StringFactoryTest extends TestCase
{
    private StringFactory $stringFactory;

    protected function setUp(): void
    {
        $this->stringFactory = new StringFactory();
    }

    public function testConstructorSetsInitialString(): void
    {
        $initialString = 'Hello World';
        $factory = new StringFactory($initialString);
        $this->assertSame($initialString, $factory->toString());
    }

    public function testRandomGeneratesStringOfSpecifiedLength(): void
    {
        $length = 15;
        $randomString = $this->stringFactory->random($length)->toString();
        $this->assertSame($length, strlen($randomString));
    }

    public function testRandomWithNumericCharset(): void
    {
        $randomString = $this->stringFactory->random(10, 'numeric')->toString();
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $randomString);
    }

    public function testRandomWithAlphaCharset(): void
    {
        $randomString = $this->stringFactory->random(10, 'alpha')->toString();
        $this->assertMatchesRegularExpression('/^[a-zA-Z]+$/', $randomString);
    }

    public function testRandomWithAlphaNumericCharset(): void
    {
        $randomString = $this->stringFactory->random(10, 'alphanumeric')->toString();
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $randomString);
    }

    public function testSlugifyConvertsStringToSlug(): void
    {
        $string = 'This is a test string!';
        $slug = $this->stringFactory = new StringFactory($string);
        $this->assertSame('this-is-a-test-string', $slug->slugify()->toString());
    }

    public function testSlugifyHandlesSpecialCharacters(): void
    {
        $string = 'Special @#$%^&*() Characters 123!';
        $slug = $this->stringFactory = new StringFactory($string);
        $this->assertSame('special-characters-123', $slug->slugify()->toString());
    }

    public function testEncryptAndDecrypt(): void
    {
        $originalString = 'Secret message to be encrypted';
        $key = 'encryption-key-for-testing';
        
        $encrypted = (new StringFactory($originalString))->encrypt($key)->toString();
        $decrypted = (new StringFactory($encrypted))->decrypt($key)->toString();
        
        $this->assertNotSame($originalString, $encrypted);
        $this->assertSame($originalString, $decrypted);
    }

    public function testEncryptWithDifferentMethod(): void
    {
        $originalString = 'Secret message to be encrypted';
        $key = 'encryption-key-for-testing';
        $method = 'aes-128-cbc';
        
        $encrypted = (new StringFactory($originalString))->encrypt($key, $method)->toString();
        $decrypted = (new StringFactory($encrypted))->decrypt($key, $method)->toString();
        
        $this->assertNotSame($originalString, $encrypted);
        $this->assertSame($originalString, $decrypted);
    }

	// TO BE FIXED IN FUTURE
    // public function testDecryptWithInvalidHmac(): void
    // {
    //     $this->expectException(\Exception::class);
        
    //     $factory = new StringFactory();
        
    //     // Create encryption key and IV
    //     $key = $factory->random(32);
    //     $iv = $factory->random(16);
        
    //     // Encrypt a test string
    //     $data = "test data";
    //     $encrypted = $factory->encrypt($data, $key, $iv);
        
    //     // Make sure we have a string result before proceeding
    //     $this->assertIsString($encrypted);
        
    //     // Create a tampered version that will fail HMAC validation
    //     // We'll assume the encrypted format is "ciphertext:hmac"
    //     $parts = explode(':', $encrypted);
        
    //     // Modify the first part (ciphertext) but keep the HMAC the same
    //     if (isset($parts[0]) && isset($parts[1])) {
    //         $parts[0] = base64_encode('tampered'); // Replace with tampered data
    //         $tampered = implode(':', $parts);
            
    //         // This should throw an Exception due to HMAC validation failure
    //         $factory->decrypt($tampered, $key, $iv);
    //     } else {
    //         // If the format isn't as expected, fail the test
    //         $this->fail("Encrypted string doesn't have the expected format");
    //     }
    // }

    public function testTruncateWithLongString(): void
    {
        $longString = str_repeat('a', 200);
        $truncated = (new StringFactory($longString))->truncate(100)->toString();
        
        $this->assertSame(103, strlen($truncated)); // 100 characters + 3 for '...'
        $this->assertSame(substr($longString, 0, 100) . '...', $truncated);
    }

    public function testTruncateWithCustomAppend(): void
    {
        $longString = str_repeat('a', 200);
        $truncated = (new StringFactory($longString))->truncate(50, '[more]')->toString();
        
        $this->assertSame(56, strlen($truncated)); // 50 characters + 6 for '[more]'
        $this->assertSame(substr($longString, 0, 50) . '[more]', $truncated);
    }

    public function testTruncateWithShortString(): void
    {
        $shortString = 'Short string';
        $truncated = (new StringFactory($shortString))->truncate(100)->toString();
        
        $this->assertSame($shortString, $truncated);
    }

    public function testToStringReturnsString(): void
    {
        $string = 'Test string';
        $this->assertSame($string, (new StringFactory($string))->toString());
    }

    public function testMagicMethodReturnsString(): void
    {
        $string = 'Test string';
        $this->assertSame($string, (string)(new StringFactory($string)));
    }
}