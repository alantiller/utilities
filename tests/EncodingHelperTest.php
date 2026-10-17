<?php declare(strict_types=1);

namespace Alantiller\Utilities\Tests;

use Alantiller\Utilities\Helpers\EncodingHelper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Verify RFC 4648 vectors, binary data and canonical decoder validation. */
final class EncodingHelperTest extends TestCase
{
    /**
     * Provide RFC 4648 base32 test vectors.
     * @return iterable<array{string, string}> Original bytes and padded base32.
     */
    public static function base32_vectors(): iterable
    {
        yield ['', ''];
        yield ['f', 'MY======'];
        yield ['fo', 'MZXQ===='];
        yield ['foo', 'MZXW6==='];
        yield ['foob', 'MZXW6YQ='];
        yield ['fooba', 'MZXW6YTB'];
        yield ['foobar', 'MZXW6YTBOI======'];
    }

    /**
     * Match standard vectors with padded, unpadded and lowercase input.
     * @param string $plain Original bytes.
     * @param string $encoded Expected padded base32.
     */
    #[DataProvider('base32_vectors')]
    public function test_base32_vectors(string $plain, string $encoded): void
    {
        $encoding = new EncodingHelper();
        self::assertSame($encoded, $encoding->base32_encode($plain, true));
        self::assertSame(rtrim($encoded, '='), $encoding->base32_encode($plain));
        self::assertSame($plain, $encoding->base32_decode($encoded));
        self::assertSame($plain, $encoding->base32_decode(strtolower(rtrim($encoded, '='))));
    }

    /** Verify all byte values and long buffers round-trip without overflowing bit buffers. */
    public function test_binary_round_trips(): void
    {
        $encoding = new EncodingHelper();
        $bytes = implode('', array_map(chr(...), range(0, 255)));
        foreach (['', 'f', 'fo', 'foo', $bytes, str_repeat($bytes, 100)] as $value) {
            self::assertSame($value, $encoding->base32_decode($encoding->base32_encode($value)));
            self::assertSame($value, $encoding->base64_url_decode($encoding->base64_url_encode($value)));
        }
        self::assertSame('f', $encoding->base64_url_decode('Zg=='));
        self::assertSame('-_8', $encoding->base64_url_encode("\xfb\xff"));
    }

    /**
     * Supply wrong alphabets, impossible lengths, padding errors and nonzero trailing bits.
     * @return iterable<array{string, string}> Decoder name and invalid text.
     */
    public static function malformed_encodings(): iterable
    {
        foreach (['A', 'MZ', 'MY=', 'MY=======', 'MY======\n', 'M0', '=MY', 'MY A'] as $value) {
            yield ['base32_decode', $value];
        }
        foreach (['A', 'Zh', 'Zg=', 'Zg===', 'Zg==\n', 'Zg+', '=Zg', ' Zg', '===='] as $value) {
            yield ['base64_url_decode', $value];
        }
    }

    /**
     * Ensure malformed encodings are never silently truncated or mapped to empty input.
     * @param string $method Decoder method name.
     * @param string $value Malformed encoded value.
     */
    #[DataProvider('malformed_encodings')]
    public function test_malformed_encodings(string $method, string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new EncodingHelper())->{$method}($value);
    }
}
