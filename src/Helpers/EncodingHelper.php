<?php declare(strict_types=1);

namespace Alantiller\Utilities\Helpers;

use InvalidArgumentException;

/** Canonical RFC 4648 base64url and base32 encoding of arbitrary bytes. */
final class EncodingHelper
{
    /**
     * Encode bytes for URLs without padding.
     * @param string $bytes Arbitrary binary data, including an empty string.
     * @return string Unpadded base64url text.
     */
    public function base64_url_encode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * Decode canonical base64url, accepting either correct padding or no padding.
     * @param string $encoded URL alphabet text; whitespace is rejected.
     * @return string Original bytes, including empty input as an empty string.
     * @throws InvalidArgumentException If alphabet, padding, length or unused bits are invalid.
     */
    public function base64_url_decode(string $encoded): string
    {
        $plain = rtrim($encoded, '=');
        $padding = (4 - strlen($plain) % 4) % 4;
        if (preg_match('/^[A-Za-z0-9_-]*={0,2}$/D', $encoded) !== 1 || strlen($plain) % 4 === 1
            || ($encoded !== $plain && $encoded !== $plain . str_repeat('=', $padding))) {
            throw new InvalidArgumentException('Invalid base64url encoding.');
        }
        $bytes = base64_decode(strtr($plain, '-_', '+/'), true);
        if ($bytes === false || $this->base64_url_encode($bytes) !== $plain) {
            throw new InvalidArgumentException('Invalid base64url encoding.');
        }
        return $bytes;
    }

    /**
     * Encode bytes using uppercase RFC 4648 base32.
     * @param string $bytes Arbitrary binary data.
     * @param bool $padded Whether to append RFC padding to a multiple of eight.
     * @return string Base32 text, empty for empty input.
     */
    public function base32_encode(string $bytes, bool $padded = false): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $buffer = 0;
        $bits = 0;
        $output = '';
        foreach (str_split($bytes) as $byte) {
            $buffer = ($buffer << 8) | ord($byte);
            $bits += 8;
            while ($bits >= 5) {
                $bits -= 5;
                $output .= $alphabet[($buffer >> $bits) & 31];
            }
            $buffer &= (1 << $bits) - 1;
        }
        if ($bits > 0) {
            $output .= $alphabet[($buffer << (5 - $bits)) & 31];
        }
        return $padded ? str_pad($output, (int) (ceil(strlen($output) / 8) * 8), '=') : $output;
    }

    /**
     * Decode canonical base32, accepting lowercase and correct optional padding.
     * @param string $encoded Base32 text without whitespace or separators.
     * @return string Decoded bytes, empty for empty input.
     * @throws InvalidArgumentException If alphabet, padding, length or unused bits are invalid.
     */
    public function base32_decode(string $encoded): string
    {
        $encoded = strtoupper($encoded);
        $plain = rtrim($encoded, '=');
        $padding = (8 - strlen($plain) % 8) % 8;
        if (preg_match('/^[A-Z2-7]*={0,6}$/D', $encoded) !== 1 || !in_array(strlen($plain) % 8, [0, 2, 4, 5, 7], true)
            || ($encoded !== $plain && $encoded !== $plain . str_repeat('=', $padding))) {
            throw new InvalidArgumentException('Invalid base32 encoding.');
        }
        $buffer = 0;
        $bits = 0;
        $output = '';
        foreach (str_split($plain) as $character) {
            $buffer = ($buffer << 5) | (int) strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', $character);
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $output .= chr(($buffer >> $bits) & 255);
            }
            $buffer &= (1 << $bits) - 1;
        }
        if ($buffer !== 0) {
            throw new InvalidArgumentException('Invalid base32 trailing bits.');
        }
        return $output;
    }
}
