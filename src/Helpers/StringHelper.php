<?php declare(strict_types=1);

namespace Alantiller\Utilities\Helpers;

use InvalidArgumentException;

/** Secure random strings and predictable ASCII slugs. */
final class StringHelper
{
    public const ALPHANUMERIC = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Generate a cryptographically random string from a unique ASCII alphabet.
     * @param int $length Number of characters; zero returns an empty string.
     * @param string $characters Nonempty, distinct printable ASCII characters.
     * @return string Random string of exactly the requested length.
     * @throws InvalidArgumentException If length is negative or alphabet is invalid.
     * @throws \Exception If the system random source fails.
     */
    public function random_string(int $length = 10, string $characters = self::ALPHANUMERIC): string
    {
        if ($length < 0 || $characters === '' || preg_match('/[^\x21-\x7e]/', $characters) === 1
            || count(array_unique(str_split($characters))) !== strlen($characters)) {
            throw new InvalidArgumentException('Use a nonnegative length and a unique printable ASCII alphabet.');
        }
        $result = '';
        for ($index = 0, $last = strlen($characters) - 1; $index < $length; $index++) {
            $result .= $characters[random_int(0, $last)];
        }
        return $result;
    }

    /**
     * Generate random ASCII letters and digits.
     * @param int $length Number of characters, at least zero.
     * @return string Random alphanumeric text.
     * @throws InvalidArgumentException If length is negative.
     * @throws \Exception If the system random source fails.
     */
    public function random_alphanumeric(int $length = 10): string
    {
        return $this->random_string($length);
    }

    /**
     * Generate a numeric string, preserving leading zeros.
     * @param int $length Number of digits, at least zero.
     * @return string Random ASCII digits.
     * @throws InvalidArgumentException If length is negative.
     * @throws \Exception If the system random source fails.
     */
    public function random_numeric(int $length = 10): string
    {
        return $this->random_string($length, '0123456789');
    }

    /**
     * Generate random mixed-case ASCII letters.
     * @param int $length Number of letters, at least zero.
     * @return string Random alphabetic text.
     * @throws InvalidArgumentException If length is negative.
     * @throws \Exception If the system random source fails.
     */
    public function random_alphabetic(int $length = 10): string
    {
        return $this->random_string($length, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }

    /**
     * Generate a URL-safe token with explicit entropy measured in bytes.
     * @param int $bytes Random byte count, at least one; 32 supplies 256 bits.
     * @return string Unpadded base64url token; length is ceil(bytes * 4 / 3).
     * @throws InvalidArgumentException If the byte count is not positive.
     * @throws \Exception If the system random source fails.
     */
    public function random_token(int $bytes = 32): string
    {
        if ($bytes < 1) {
            throw new InvalidArgumentException('Token byte count must be positive.');
        }
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    /**
     * Convert ASCII words to lowercase hyphenated text.
     * Non-ASCII characters are separators; no locale-dependent transliteration occurs.
     * @param string $value Input text.
     * @return string Slug, possibly empty; never a uniqueness guarantee.
     */
    public function slugify(string $value): string
    {
        return strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $value), '-'));
    }
}
