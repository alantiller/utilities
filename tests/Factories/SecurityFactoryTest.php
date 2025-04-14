<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities\Tests\Factories;

use PHPUnit\Framework\TestCase;
use WebsiteSQL\Utilities\Factories\SecurityFactory;
use DateTime;
use Exception;

class SecurityFactoryTest extends TestCase
{
    private SecurityFactory $securityFactory;
    
    protected function setUp(): void
    {
        $this->securityFactory = new SecurityFactory();
    }
    
    public function testParseCookies(): void
    {
        $cookieHeader = 'name=value; sessionId=abc123; preferences=dark-mode';
        $parsedCookies = $this->securityFactory->parseCookies($cookieHeader);
        
        $this->assertIsArray($parsedCookies);
        $this->assertCount(3, $parsedCookies);
        $this->assertEquals('value', $parsedCookies['name']);
        $this->assertEquals('abc123', $parsedCookies['sessionId']);
        $this->assertEquals('dark-mode', $parsedCookies['preferences']);
    }
    
    public function testParseCookiesWithEmptyCookie(): void
    {
        $cookieHeader = '';
        $parsedCookies = $this->securityFactory->parseCookies($cookieHeader);
        
        $this->assertIsArray($parsedCookies);
        $this->assertEmpty($parsedCookies);
    }
    
    public function testParseCookiesWithMalformedCookie(): void
    {
        $cookieHeader = 'malformed-cookie; name=value';
        $parsedCookies = $this->securityFactory->parseCookies($cookieHeader);
        
        $this->assertIsArray($parsedCookies);
        $this->assertCount(1, $parsedCookies);
        $this->assertEquals('value', $parsedCookies['name']);
    }
    
    public function testParseAuthorizationWithBearerToken(): void
    {
        $authHeader = 'Bearer token123';
        $token = $this->securityFactory->parseAuthorization($authHeader);
        
        $this->assertEquals('token123', $token);
    }
    
    public function testParseAuthorizationWithNonBearerToken(): void
    {
        $authHeader = 'Basic dXNlcjpwYXNzd29yZA==';
        $token = $this->securityFactory->parseAuthorization($authHeader);
        
        $this->assertNull($token);
    }
    
    public function testParseAuthorizationWithEmptyHeader(): void
    {
        $token = $this->securityFactory->parseAuthorization('');
        
        $this->assertNull($token);
    }
    
    public function testCalculateExpiryDateWithValidDates(): void
    {
        $createdAt = new DateTime();
        $maxAge = 3600; // 1 hour
        $refreshAge = 7200; // 2 hours
        
        $refreshDate = $this->securityFactory->calculateExpiryDate($createdAt, $maxAge, $refreshAge);
        
        $this->assertInstanceOf(DateTime::class, $refreshDate);
        
        // The refresh date should be 2 hours in the future
        $expectedRefreshDate = clone $createdAt;
        $expectedRefreshDate->modify('+' . $refreshAge . ' seconds');
        
        // Allow for a small difference due to execution time
        $this->assertEquals($expectedRefreshDate->format('Y-m-d H:i'), $refreshDate->format('Y-m-d H:i'));
    }
    
    public function testCalculateExpiryDateWithExpiredToken(): void
    {
        $createdAt = new DateTime('-2 hours'); // Token created 2 hours ago
        $maxAge = 3600; // 1 hour (already expired)
        $refreshAge = 7200; // 2 hours
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Token has expired');
        
        $this->securityFactory->calculateExpiryDate($createdAt, $maxAge, $refreshAge);
    }
    
    public function testCalculateExpiryDateWithRefreshDateLessThanMaxAge(): void
    {
        $createdAt = new DateTime();
        $maxAge = 7200; // 2 hours
        $refreshAge = 3600; // 1 hour (less than max age)
        
        $refreshDate = $this->securityFactory->calculateExpiryDate($createdAt, $maxAge, $refreshAge);
        
        // The refresh date should be equal to the expiry date (2 hours)
        $expectedExpiryDate = clone $createdAt;
        $expectedExpiryDate->modify('+' . $maxAge . ' seconds');
        
        $this->assertEquals($expectedExpiryDate->format('Y-m-d H:i'), $refreshDate->format('Y-m-d H:i'));
    }
    
    public function testGenerateCookieHeaderWithDefaultOptions(): void
    {
        $name = 'session';
        $value = 'abc123';
        
        $cookieHeader = $this->securityFactory->generateCookieHeader($name, $value);
        
        $this->assertStringContainsString('session=abc123;', $cookieHeader);
        $this->assertStringContainsString('Path=/;', $cookieHeader);
        $this->assertStringContainsString('HttpOnly;', $cookieHeader);
        $this->assertStringContainsString('SameSite=Strict;', $cookieHeader);
        $this->assertStringContainsString('Secure;', $cookieHeader);
    }
    
    public function testGenerateCookieHeaderWithCustomOptions(): void
    {
        $name = 'preference';
        $value = 'dark-mode';
        $options = [
            'domain' => 'example.com',
            'path' => '/admin',
            'expires' => new DateTime('+1 day'),
            'httpOnly' => false,
            'sameSite' => 'Lax',
            'secure' => false
        ];
        
        $cookieHeader = $this->securityFactory->generateCookieHeader($name, $value, $options);
        
        $this->assertStringContainsString('preference=dark-mode;', $cookieHeader);
        $this->assertStringContainsString('Domain=example.com;', $cookieHeader);
        $this->assertStringContainsString('Path=/admin;', $cookieHeader);
        $this->assertStringNotContainsString('HttpOnly;', $cookieHeader);
        $this->assertStringContainsString('SameSite=Lax;', $cookieHeader);
        $this->assertStringNotContainsString('Secure;', $cookieHeader);
    }
    
    public function testHashPassword(): void
    {
        $password = 'SecurePassword123';
        $hash = $this->securityFactory->hashPassword($password);
        
        $this->assertNotEquals($password, $hash);
        $this->assertStringStartsWith('$2y$', $hash); // BCrypt hash starts with $2y$
    }
    
    public function testVerifyPasswordWithCorrectPassword(): void
    {
        $password = 'SecurePassword123';
        $hash = $this->securityFactory->hashPassword($password);
        
        $isValid = $this->securityFactory->verifyPassword($password, $hash);
        
        $this->assertTrue($isValid);
    }
    
    public function testVerifyPasswordWithIncorrectPassword(): void
    {
        $password = 'SecurePassword123';
        $incorrectPassword = 'WrongPassword456';
        $hash = $this->securityFactory->hashPassword($password);
        
        $isValid = $this->securityFactory->verifyPassword($incorrectPassword, $hash);
        
        $this->assertFalse($isValid);
    }
    
    public function testGenerateCSRFToken(): void
    {
        $token = $this->securityFactory->generateCSRFToken();
        
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex characters
    }
    
    public function testValidateCSRFTokenWithValidToken(): void
    {
        $token = $this->securityFactory->generateCSRFToken();
        
        $isValid = $this->securityFactory->validateCSRFToken($token, $token);
        
        $this->assertTrue($isValid);
    }
    
    public function testValidateCSRFTokenWithInvalidToken(): void
    {
        $token = $this->securityFactory->generateCSRFToken();
        $fakeToken = $this->securityFactory->generateCSRFToken();
        
        $isValid = $this->securityFactory->validateCSRFToken($fakeToken, $token);
        
        $this->assertFalse($isValid);
    }
}