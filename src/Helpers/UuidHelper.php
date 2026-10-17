<?php declare(strict_types=1);

namespace Alantiller\Utilities\Helpers;

use InvalidArgumentException;

/** UUID v1-v4 generation and canonical/binary conversion using native PHP primitives. */
final class UuidHelper
{
    public const NAMESPACE_DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
    public const NAMESPACE_URL = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
    public const NAMESPACE_OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';
    public const NAMESPACE_X500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';
    public const DOMAIN_PERSON = 0;
    public const DOMAIN_GROUP = 1;
    public const DOMAIN_ORG = 2;

    /**
     * Generate a time-based UUID using a random multicast node by default.
     * This avoids discovering or exposing the host MAC address. Uniqueness with
     * caller-supplied nodes/sequences remains the caller's responsibility.
     * @param string|null $node Twelve hexadecimal digits, or null for a fresh random node.
     * @param int|null $clock_sequence Sequence 0..16383, or null for a random sequence.
     * @return string Lowercase canonical version 1 UUID.
     * @throws InvalidArgumentException If node or sequence is invalid.
     * @throws \Exception If randomness or native UUID generation fails.
     */
    public function v1(?string $node = null, ?int $clock_sequence = null): string
    {
        $node = $this->node($node);
        if ($clock_sequence !== null && ($clock_sequence < 0 || $clock_sequence > 16383)) {
            throw new InvalidArgumentException('Version 1 clock sequence must be 0..16383.');
        }
        return $this->time_uuid(1, $node, $clock_sequence ?? random_int(0, 16383));
    }

    /**
     * Generate a DCE UUID with an explicit local identifier and domain.
     * V2 timestamps lose precision; fixed nodes permit only 64 sequence values
     * per identifier/domain in roughly seven minutes. Prefer v4 for general IDs.
     * @param int $local_identifier Unsigned 32-bit local account/group/organization ID.
     * @param int $domain DOMAIN_PERSON (0), DOMAIN_GROUP (1), or DOMAIN_ORG (2).
     * @param string|null $node Twelve hexadecimal digits; null uses a fresh random node.
     * @param int|null $clock_sequence Sequence 0..63; null selects a random sequence.
     * @return string Lowercase canonical version 2 UUID.
     * @throws InvalidArgumentException If identifier, domain, node or sequence is invalid.
     * @throws \Exception If randomness or native UUID generation fails.
     */
    public function v2(int $local_identifier, int $domain = self::DOMAIN_PERSON, ?string $node = null, ?int $clock_sequence = null): string
    {
        if ($local_identifier < 0 || $local_identifier > 4294967295 || !in_array($domain, [0, 1, 2], true)
            || ($clock_sequence !== null && ($clock_sequence < 0 || $clock_sequence > 63))) {
            throw new InvalidArgumentException('Invalid DCE identifier, domain or clock sequence.');
        }
        return $this->time_uuid(2, $this->node($node), $clock_sequence ?? random_int(0, 63), $local_identifier, $domain);
    }

    /**
     * Generate a deterministic namespace/name UUID using MD5 as specified for v3.
     * @param string $namespace Canonical v1-v4 UUID namespace or a NAMESPACE_* constant.
     * @param string $name Exact name bytes; names are case-sensitive and not normalized.
     * @return string Lowercase version 3 UUID, stable for the same namespace/name.
     * @throws InvalidArgumentException If namespace is not a supported UUID.
     * @throws \Exception If native UUID generation fails.
     */
    public function v3(string $namespace, string $name): string
    {
        return $this->format_bytes(md5($this->to_bytes($namespace) . $name, true), 3);
    }

    /**
     * Generate a cryptographically random UUID; this is the default ID choice.
     * @return string Lowercase canonical version 4 UUID.
     * @throws \Exception If the system random source or UUID generator fails.
     */
    public function v4(): string
    {
        return $this->format_bytes(random_bytes(16), 4);
    }

    /**
     * Check canonical UUID syntax, RFC variant and supported version.
     * @param string $value Hyphenated UUID; uppercase hexadecimal is accepted.
     * @param int|null $version Required version 1..4, or null for any supported version.
     * @return bool False for malformed, nil, unsupported-version or wrong-variant UUIDs.
     */
    public function is_valid(string $value, ?int $version = null): bool
    {
        return ($version === null || in_array($version, [1, 2, 3, 4], true))
            && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-4][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $value) === 1
            && ($version === null || (int) $value[14] === $version);
    }

    /**
     * Validate and lowercase a supported UUID without accepting URNs or braces.
     * @param string $value Canonical v1-v4 UUID.
     * @return string Lowercase UUID.
     * @throws InvalidArgumentException If UUID is malformed or unsupported.
     */
    public function normalize(string $value): string
    {
        if (!$this->is_valid($value)) {
            throw new InvalidArgumentException('Expected a canonical RFC-variant UUID version 1..4.');
        }
        return strtolower($value);
    }

    /**
     * Extract the validated UUID version.
     * @param string $value Canonical v1-v4 UUID.
     * @return int UUID version, 1..4.
     * @throws InvalidArgumentException If UUID is invalid or unsupported.
     */
    public function version(string $value): int
    {
        return (int) $this->normalize($value)[14];
    }

    /**
     * Convert a supported UUID to its network-order binary representation.
     * @param string $value Canonical v1-v4 UUID.
     * @return string Exactly 16 bytes, suitable for a BINARY(16) column.
     * @throws InvalidArgumentException If UUID is invalid or unsupported.
     */
    public function to_bytes(string $value): string
    {
        return (string) hex2bin(str_replace('-', '', $this->normalize($value)));
    }

    /**
     * Convert binary UUID bytes to canonical text and validate version/variant.
     * @param string $bytes Exactly 16 network-order bytes.
     * @return string Lowercase v1-v4 UUID.
     * @throws InvalidArgumentException If byte count, variant or version is invalid.
     */
    public function from_bytes(string $bytes): string
    {
        if (strlen($bytes) !== 16) {
            throw new InvalidArgumentException('A UUID must contain exactly 16 bytes.');
        }
        return $this->normalize($this->format_bytes($bytes));
    }

    /**
     * Generate a batch of random v4 UUIDs.
     * @param int $count Number to generate; zero returns an empty list.
     * @return list<string> UUIDs in generation order.
     * @throws InvalidArgumentException If count is negative.
     * @throws \Exception If randomness or UUID generation fails.
     */
    public function generate_multiple(int $count): array
    {
        if ($count < 0) {
            throw new InvalidArgumentException('UUID count must not be negative.');
        }
        $values = [];
        for ($index = 0; $index < $count; $index++) {
            $values[] = $this->v4();
        }
        return $values;
    }

    /**
     * Return a short random hex label; this is not a UUID and can collide.
     * @param int $length Hexadecimal characters, 1..32; eight provides only 32 bits.
     * @return string Lowercase random hexadecimal label.
     * @throws InvalidArgumentException If length is outside 1..32.
     * @throws \Exception If the system random source fails.
     */
    public function short_id(int $length = 8): string
    {
        if ($length < 1 || $length > 32) {
            throw new InvalidArgumentException('Short ID length must be 1..32.');
        }
        return substr(bin2hex(random_bytes(16)), 0, $length);
    }

    /**
     * Assemble timestamp, sequence, node and optional DCE fields in network order.
     * Fresh random nodes are the default: no process-global state, MAC lookup or
     * persistent clock storage is used. Fixed-node uniqueness is caller-owned.
     * @param int $version Time UUID version, 1 or 2, validated by the public caller.
     * @param string $node Validated twelve-digit hexadecimal node.
     * @param int $sequence Validated 14-bit v1 or 6-bit v2 clock sequence.
     * @param int $identifier Unsigned DCE identifier, used only for v2.
     * @param int $domain DCE domain, used only for v2.
     * @return string Canonical time-based UUID.
     */
    private function time_uuid(int $version, string $node, int $sequence, int $identifier = 0, int $domain = 0): string
    {
        $time = gettimeofday();
        // UUID ticks are 100ns since 1582-10-15; PHP supplies microsecond precision.
        $ticks = $time['sec'] * 10000000 + $time['usec'] * 10 + 122192928000000000;
        $low = $version === 2 ? $identifier : ($ticks & 0xffffffff);
        $middle = ($ticks >> 32) & 0xffff;
        $high = (($ticks >> 48) & 0x0fff) | ($version << 12);
        $sequence_high = 0x80 | ($version === 2 ? $sequence : ($sequence >> 8));
        $sequence_low = $version === 2 ? $domain : ($sequence & 0xff);
        return sprintf('%08x-%04x-%04x-%02x%02x-%s', $low, $middle, $high, $sequence_high, $sequence_low, $node);
    }

    /**
     * Render sixteen bytes, optionally setting the version and RFC variant bits.
     * @param string $bytes Exactly sixteen bytes supplied by internal callers.
     * @param int|null $version Version to apply, or null to retain original fields.
     * @return string Lowercase hyphenated UUID.
     */
    private function format_bytes(string $bytes, ?int $version = null): string
    {
        if ($version !== null) {
            $bytes[6] = chr((ord($bytes[6]) & 0x0f) | ($version << 4));
            $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        }
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
            . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
    }

    /**
     * Resolve an explicit node or generate a random multicast node.
     * @param string|null $node Twelve hexadecimal digits, or null.
     * @return string Twelve lowercase hexadecimal digits.
     * @throws InvalidArgumentException If the explicit node is malformed.
     * @throws \Exception If randomness fails.
     */
    private function node(?string $node): string
    {
        if ($node !== null) {
            if (preg_match('/^[0-9a-f]{12}$/iD', $node) !== 1) {
                throw new InvalidArgumentException('A node must contain twelve hexadecimal digits.');
            }
            return strtolower($node);
        }
        $bytes = random_bytes(6);
        $bytes[0] = chr(ord($bytes[0]) | 0x01);
        return bin2hex($bytes);
    }
}
