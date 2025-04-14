<?php declare(strict_types=1);

namespace WebsiteSQL\Utilities\Factories;

use DateTime;
use Exception;

class SecurityFactory
{
    public function parseCookies(string $cookieHeader): array
    {
        $cookies = explode(';', $cookieHeader);
        $output = [];

        foreach ($cookies as $cookie) {
            $cookieParts = explode('=', trim($cookie), 2);
            if (count($cookieParts) === 2) {
                list($name, $value) = $cookieParts;
                $output[$name] = $value;
            }
        }

        return $output;
    }

    public function parseAuthorization(string $authorizationHeader): ?string
    {
        $token = null;
        if (!empty($authorizationHeader)) {
            if (strpos($authorizationHeader, 'Bearer ') === 0) {
                $token = substr($authorizationHeader, 7); // Remove "Bearer "
            }
        }
        return $token;
    }

    public function calculateExpiryDate(DateTime $createdAt, int $maxAge, int $refreshAge): DateTime
    {
        // Get the times
        $currentTime = new DateTime();

        // Calculate the maxmium age of the token
        $expiryDate = clone $createdAt;
        $expiryDate->modify('+' . $maxAge . ' seconds');

        // If the expiry date is less than the current time throw an error
        if ($expiryDate < $currentTime) {
            throw new Exception('Token has expired');
        }

        // Calculate the refresh age of the token
        $refreshDate = clone $createdAt;
        $refreshDate->modify('+' . $refreshAge . ' seconds');

        // If the refresh date is less the maximum age, set the refresh date to the maximum age
        if ($refreshDate < $expiryDate) {
            $refreshDate = clone $expiryDate;
        }

        return $refreshDate;
    }

    public function generateCookieHeader(string $name, string $value, array $options = []): string
    {
        // Get the options or set the defaults
        $domain = $options['domain'] ?? null;
        $path = $options['path'] ?? '/';
        $expires = $options['expires'] ?? new DateTime('+1 hour');
        $httpOnly = $options['httpOnly'] ?? true;
        $sameSite = $options['sameSite'] ?? 'Strict';
        $secure = $options['secure'] ?? true;

        // Create the cookie string
        $cookieString = sprintf('%s=%s; ', $name, $value);

        // Add the domain to the cookie string
        if (!empty($domain)) {
            $cookieString .= sprintf('Domain=%s; ', $domain);
        }

        // Add the path
        $cookieString .= sprintf('Path=%s; ', $path);

        // Add the expiry date
        $cookieString .= sprintf('Expires=%s; ', $expires->format(DateTime::COOKIE));

        // Add the HttpOnly flag
        if ($httpOnly) {
            $cookieString .= 'HttpOnly; ';
        }

        // Add the SameSite flag
        if (!empty($sameSite)) {
            $cookieString .= sprintf('SameSite=%s; ', $sameSite);
        }

        // Add the Secure flag
        if ($secure) {
            $cookieString .= 'Secure; ';
        }

        return $cookieString;
    }
    
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    public function generateCSRFToken(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    public function validateCSRFToken(string $token, string $storedToken): bool
    {
        return hash_equals($token, $storedToken);
    }
}
