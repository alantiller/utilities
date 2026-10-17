<?php declare(strict_types=1);

namespace Alantiller\Utilities\Tests;

use Alantiller\Utilities;
use Alantiller\Utilities\Helpers\StringHelper;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Verify independent helpers and the package entry point without a framework. */
final class HelpersTest extends TestCase
{
    /** Verify the facade exposes working helpers with no Flight initialization. */
    public function test_facade_and_strings(): void
    {
        $utilities = new Utilities('Europe/London');
        self::assertSame('2024-07-01 11:00:00', $utilities->dates->to_utc('2024-07-01 12:00:00'));
        self::assertSame('', $utilities->strings->random_string(0));
        self::assertMatchesRegularExpression('/^[0-9]{50}$/', $utilities->strings->random_numeric(50));
        self::assertMatchesRegularExpression('/^[a-zA-Z]{50}$/', $utilities->strings->random_alphabetic(50));
        self::assertMatchesRegularExpression('/^[a-zA-Z0-9]{50}$/', $utilities->strings->random_alphanumeric(50));
        self::assertSame('AAAA', $utilities->strings->random_string(4, 'A'));
        self::assertSame('hello-world', $utilities->strings->slugify(' Hello -- world! '));
        self::assertSame('', $utilities->strings->slugify('你好'));
        self::assertSame(32, strlen($utilities->encoding->base64_url_decode($utilities->strings->random_token())));
        self::assertTrue($utilities->uuids->is_valid($utilities->uuids->v4(), 4));
    }

    /**
     * Supply invalid random-string arguments.
     * @return iterable<array{int, string}> Invalid length/alphabet pairs.
     */
    public static function invalid_alphabets(): iterable
    {
        yield [-1, 'abc'];
        yield [1, ''];
        yield [1, 'aab'];
        yield [1, 'é'];
        yield [1, "a\0"];
    }

    /**
     * Reject biased, multibyte and invalid alphabets.
     * @param int $length Invalid length or length paired with an invalid alphabet.
     * @param string $alphabet Alphabet to validate.
     */
    #[DataProvider('invalid_alphabets')]
    public function test_invalid_alphabets(int $length, string $alphabet): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new StringHelper())->random_string($length, $alphabet);
    }

    /** Verify invalid token lengths fail before requesting random bytes. */
    public function test_invalid_token_length(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new StringHelper())->random_token(0);
    }

    /** Verify field presence, Unicode lengths, JSON scalars and optional strings. */
    public function test_validation_contracts(): void
    {
        $validation = (new Utilities())->validation;
        self::assertSame(['missing', 'null', 'blank'], array_keys($validation->required_fields(
            ['null' => null, 'blank' => ' ', 'zero' => 0, 'false' => false], ['missing', 'null', 'blank', 'zero', 'false']
        )));
        self::assertTrue($validation->has_length('é😊', 2, 2));
        self::assertFalse($validation->has_length("\xff"));
        self::assertTrue($validation->is_email('alan@example.com'));
        self::assertFalse($validation->is_email('bad'));
        self::assertTrue($validation->is_json('null'));
        self::assertTrue($validation->is_json('[]'));
        self::assertFalse($validation->is_json('{'));
        self::assertNull($validation->nullable_string([], 'name', 10));
        self::assertNull($validation->nullable_string(['name' => '  '], 'name', 10));
        self::assertSame('é', $validation->nullable_string(['name' => ' é '], 'name', 1));
        self::assertTrue($validation->matches('abc', '/^a/'));
        self::assertFalse($validation->matches('abc', '/^z/'));
    }

    /** Verify invalid PCRE is distinguished from a nonmatch. */
    public function test_invalid_pattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Utilities())->validation->matches('abc', '/[/');
    }

    /** Verify invalid length bounds are rejected. */
    public function test_invalid_length_bounds(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Utilities())->validation->has_length('abc', 4, 2);
    }

    /** Verify optional string validation rejects overlong values. */
    public function test_overlong_optional_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Utilities())->validation->nullable_string(['name' => 'é😊'], 'name', 1);
    }

    /** Verify projections retain null values, skip absent fields and resolve duplicates. */
    public function test_array_projection(): void
    {
        $arrays = (new Utilities())->arrays;
        self::assertSame(['a' => 2, 'b' => null], $arrays->to_key_value([
            ['id' => 'a', 'value' => 1], ['id' => 'b', 'value' => null], ['id' => 'a', 'value' => 2], ['id' => 'c'],
        ], 'id', 'value'));
    }

    /** Verify array keys cannot silently coerce unsupported types. */
    public function test_invalid_projection_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Utilities())->arrays->to_key_value([['id' => [], 'value' => 1]], 'id', 'value');
    }

    /** Verify JSON objects, pagination and SSE framing without emitting a response. */
    public function test_http_helpers(): void
    {
        $http = (new Utilities())->http;
        self::assertSame([], $http->json_object('{}'));
        self::assertSame(['a' => 1], $http->json_object('{"a":1}'));
        self::assertSame(['page' => 1, 'per_page' => 100, 'offset' => 0], $http->pagination(-1, 500));
        self::assertSame(['page' => 3, 'per_page' => 10, 'offset' => 20], $http->pagination(3, 10));
        self::assertSame("event: update\ndata: {\"id\":1}\n\n", $http->sse_event('update', ['id' => 1]));
    }

    /** Reject list bodies where a JSON object is required. */
    public function test_json_list_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Utilities())->http->json_object('[]');
    }

    /** Distinguish JSON syntax failures from shape validation errors. */
    public function test_malformed_json_body(): void
    {
        $this->expectException(JsonException::class);
        (new Utilities())->http->json_object('{');
    }

    /** Reject SSE framing injection before writing any output. */
    public function test_sse_injection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Utilities())->http->sse_event("event\ndata: bad", []);
    }

    /** Reject invalid UTF-8 payloads instead of emitting incomplete SSE frames. */
    public function test_sse_invalid_payload(): void
    {
        $this->expectException(JsonException::class);
        (new Utilities())->http->sse_event('update', ["\xff"]);
    }

    /** Reject offsets beyond the supported integer range. */
    public function test_pagination_overflow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Utilities())->http->pagination(PHP_INT_MAX, 100);
    }

    /** Reject insecure SameSite=None cookies before attempting response mutation. */
    public function test_cookie_configuration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Utilities())->http->set_cookie('session', 'test', secure: false, same_site: 'None');
    }
}
