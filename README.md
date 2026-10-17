# Alan Tiller Utilities

A standalone PHP utility library for dates, random strings, UUID v1–v4, encoding, validation, arrays and HTTP data. The core has **no production Composer package dependencies** and does not require Flight, a database, a logger or either of Alan's other libraries.

Requires **64-bit PHP 8.1+** and **mbstring**. PHPUnit and PHPStan are development dependencies only. The package follows the `Alantiller` entry-point convention used by `alantiller/auth` and `alantiller/permission`.

## Installation

Work on this repository as a root Composer project:

```sh
composer install
composer check
```

An application can also consume a local checkout before publication:

```json
{
    "repositories": [{"type": "path", "url": "../utilities"}],
    "require": {"alantiller/utilities": "@dev"}
}
```

After this package is published to a configured Composer repository, applications can use `composer require alantiller/utilities`. No release has been published as part of this implementation.

## Quick start

```php
<?php declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Alantiller\Utilities;
use Alantiller\Utilities\Helpers\UuidHelper;

$utilities = new Utilities(timezone: 'Europe/London');

$utc_date = $utilities->dates->to_utc('2026-07-01 12:00:00'); // 2026-07-01 11:00:00
$iso_date = $utilities->dates->to_iso8601('2026-07-01T12:00:00+02:00');
$uuid = $utilities->uuids->v4();
$name_uuid = $utilities->uuids->v3(UuidHelper::NAMESPACE_DNS, 'example.com');
$token = $utilities->strings->random_token(32); // 256 random bits, base64url encoded
$errors = $utilities->validation->required_fields(['name' => 'Alan'], ['name', 'email']);
```

Helpers can be used independently:

```php
use Alantiller\Utilities\Helpers\DateTimeHelper;
use Alantiller\Utilities\Helpers\StringHelper;

$dates = new DateTimeHelper('UTC', 'Y-m-d H:i:s');
$strings = new StringHelper();
```

The facade's public helper properties are readonly. Its constructor validates the default timezone and has no logging, request, database or global timezone side effects. Helpers do not accumulate validation errors.

## Date and time conversion

`$utilities->dates` is a `DateTimeHelper`. It uses immutable dates, explicit timezone defaults and PHP format strings.

```php
$dates = $utilities->dates;

// Format conversion, with strict input parsing and a known source timezone.
$converted = $dates->convert(
    value: '29/02/2024 15:30',
    output_format: 'Y-m-d H:i:s',
    target_timezone: 'America/New_York',
    input_format: 'd/m/Y H:i',
    source_timezone: 'Europe/London'
); // 2024-02-29 10:30:00

$instant = $dates->parse('2024-02-29T12:34:56.123456Z');
$utc_date = $dates->to_utc($instant);
$iso_date = $dates->to_iso8601($instant, 'UTC');
$timestamp = $dates->to_timestamp('1970-01-01T00:00:00Z'); // 0
$pre_epoch = $dates->from_timestamp(-1, timezone: 'UTC'); // 1969-12-31 23:59:59
$tomorrow = $dates->add_interval('2024-03-30 12:00:00', 'P1D');
$yesterday = $dates->add_interval('2024-03-30 12:00:00', 'P1D', subtract: true);
$elapsed = $dates->difference_seconds('2024-03-30 12:00:00', '2024-03-31 12:00:00');
$relative = $dates->time_ago('2024-01-01T00:00:00Z', '2024-01-01T01:00:00Z'); // 1 hour ago
```

| Method | Result |
| --- | --- |
| `parse(value, input_format = null, source_timezone = null)` | `DateTimeImmutable` retaining the input/source timezone |
| `now(format = null, timezone = null)` | Current instant formatted in the selected timezone |
| `convert(value, output_format = null, target_timezone = null, input_format = null, source_timezone = null)` | Formatted conversion |
| `to_utc(value, source_timezone = null)` | UTC `Y-m-d H:i:s` |
| `to_iso8601(value, timezone = null)` | `Y-m-d\TH:i:s.uP`, including microseconds |
| `to_timestamp(value, source_timezone = null)` | Whole signed Unix seconds |
| `from_timestamp(timestamp, format = null, timezone = null)` | Formatted Unix seconds, including negative values |
| `add_interval(value, interval, subtract = false)` | Adjusted immutable date |
| `difference_seconds(from, to)` | Signed elapsed whole seconds |
| `time_ago(value, reference = 'now')` | English relative description, including future values |

Rules and boundaries:

- Text without a timezone uses the supplied source timezone or the helper default, never PHP's process default. An embedded offset/timezone takes precedence over the source timezone. Conversion always applies the selected target timezone.
- A `DateTimeInterface` value represents an existing instant; its timezone is preserved during parsing and it is never mutated.
- An explicit input format enables strict parsing. Unspecified fields reset to the Unix epoch; a date-only value therefore starts at midnight. Invalid calendar dates, mismatched input, trailing garbage and blank strings throw `InvalidArgumentException`.
- With no input format, PHP free-form/relative syntax such as `now` is supported; calendar warnings still cause rejection. Use a format at external input boundaries when relative dates must be disallowed.
- PHP's timezone rules resolve ambiguous or nonexistent local times during daylight-saving transitions. The helper does not reject DST gaps or choose an alternative fold. Supply an explicit UTC offset when the local instant must be unambiguous.
- Calendar intervals use PHP's month-overflow and DST behavior. In London, adding `P1D` across the spring transition can advance only 23 elapsed hours; `difference_seconds()` measures elapsed seconds. This API does not implement business-day calendars or locale-specific date parsing.
- Unix timestamps are **seconds**, not milliseconds. Microseconds survive parsing and ISO output but are discarded by whole-second timestamp and UTC database output.
- `time_ago()` uses floor-based counts, singular/plural forms, 30-day months and 365-day years. Values within 60 seconds return `just now`.

## UUIDs, implemented natively

`$utilities->uuids` is a `UuidHelper`. It uses native integer field assembly, MD5 for the prescribed v3 algorithm, and `random_bytes()`/`random_int()` for random values. No external UUID package, shell command, MAC-address discovery or process-global factory is used.

```php
use Alantiller\Utilities\Helpers\UuidHelper;

$uuids = $utilities->uuids;
$time_uuid = $uuids->v1();
$dce_uuid = $uuids->v2(local_identifier: 1001, domain: UuidHelper::DOMAIN_PERSON);
$name_uuid = $uuids->v3(UuidHelper::NAMESPACE_DNS, 'www.widgets.com');
// 3d813cbb-47fb-32ba-91df-831e1593ac29
$random_uuid = $uuids->v4();

$is_valid = $uuids->is_valid($random_uuid); // true
$is_v4 = $uuids->is_valid($random_uuid, version: 4); // true
$normalized = $uuids->normalize(strtoupper($random_uuid));
$version = $uuids->version($random_uuid); // 4
$bytes = $uuids->to_bytes($random_uuid); // 16 network-order bytes
$restored = $uuids->from_bytes($bytes);
$batch = $uuids->generate_multiple(10); // v4 UUIDs
$label = $uuids->short_id(8); // 32 random bits; NOT a UUID
```

| Generator | Inputs and behavior |
| --- | --- |
| `v1(node = null, clock_sequence = null)` | Current Gregorian timestamp; optional 12-hex-digit node and sequence 0..16383 |
| `v2(local_identifier, domain = DOMAIN_PERSON, node = null, clock_sequence = null)` | Unsigned 32-bit identifier, domain 0..2, optional node and sequence 0..63 |
| `v3(namespace, name)` | Deterministic MD5 namespace/name mapping; names are exact, case-sensitive bytes |
| `v4()` | Random UUID with 122 random bits after version and variant fields |

Namespaces: `NAMESPACE_DNS`, `NAMESPACE_URL`, `NAMESPACE_OID`, `NAMESPACE_X500`.
DCE domains: `DOMAIN_PERSON` (0), `DOMAIN_GROUP` (1), `DOMAIN_ORG` (2).

All generated UUIDs use canonical lowercase `8-4-4-4-12` text. Validation accepts uppercase but requires canonical hyphens, RFC variant and version 1..4. Nil UUIDs, versions 5..8, URNs, braces and compact forms are intentionally outside this API. Namespaces must also satisfy that supported UUID contract. Invalid arguments throw `InvalidArgumentException`; randomness failures propagate native exceptions.

V1 and v2 use a **fresh random multicast node on each call** by default and a random clock sequence. There is no persistent clock state or monotonic-order guarantee. V1 timestamp precision is one microsecond, represented in the required 100ns tick fields. With an explicit fixed node, the caller must coordinate sequences and handle clock rollback and concurrency across processes. Generation does not guarantee absence of collisions; applications needing uniqueness must enforce it at storage boundaries.

V2 embeds an account/group/organization identifier, replaces the low timestamp field and retains only six clock-sequence bits. With a fixed node and identifier/domain combination, only 64 sequence values exist in each approximately seven-minute timestamp interval. Use v4 for general-purpose random identifiers. Short labels have far less entropy and are not substitutes for UUIDs or bearer tokens. V3's MD5 is required by that UUID version and is not a password/token hash.

The v1, v3 and v4 layouts follow [RFC 4122](https://www.rfc-editor.org/rfc/rfc4122), now superseded by [RFC 9562](https://www.rfc-editor.org/rfc/rfc9562). V2 follows the DCE field convention; it is distinct from the RFC's specified generation algorithms. Tests independently decode time/DCE fields and verify the RFC Appendix B v3 vector.

## Strings

| Method on `$utilities->strings` | Behavior |
| --- | --- |
| `random_string(length = 10, characters = StringHelper::ALPHANUMERIC)` | Uniform selection from a nonempty, unique printable ASCII alphabet |
| `random_alphanumeric(length = 10)` | Mixed-case ASCII letters and digits |
| `random_numeric(length = 10)` | Digits as text, preserving leading zeros |
| `random_alphabetic(length = 10)` | Mixed-case ASCII letters |
| `random_token(bytes = 32)` | URL-safe unpadded base64 token from explicit random bytes |
| `slugify(value)` | Lowercase ASCII words separated by single hyphens |

Zero-length random strings are allowed; negative lengths and invalid alphabets throw `InvalidArgumentException`. Token byte counts must be positive. A one-character alphabet is allowed but supplies no entropy. ASCII slugs do not transliterate non-ASCII characters, are potentially empty, and do not enforce uniqueness. No locale-dependent normalization is performed.

## Encoding

`$utilities->encoding` provides:

- `base64_url_encode(bytes)` and `base64_url_decode(encoded)`.
- `base32_encode(bytes, padded: false)` and `base32_decode(encoded)`.

Both encoders support arbitrary binary data and empty input. Base32 output is uppercase; its decoder accepts lowercase. Decoders accept canonical unpadded input or correctly padded input and reject invalid alphabets, whitespace, impossible lengths, incorrect padding and nonzero unused bits with `InvalidArgumentException`. Invalid input is never silently returned as an empty decoded value. Base32 processing uses a bounded bit buffer rather than building an eight-times-larger bit string.

## Validation and arrays

`$utilities->validation` is stateless:

| Method | Result |
| --- | --- |
| `required_fields(data, required)` | `array<string, list<string>>`; absent, null and blank strings fail, while zero and false pass |
| `is_email(value)` | PHP email syntax check, with no deliverability lookup |
| `has_length(value, min = 0, max = PHP_INT_MAX)` | UTF-8 code-point length check; invalid UTF-8 returns false |
| `is_json(value)` | Syntax check accepting all JSON values, including scalars and null |
| `matches(value, pattern)` | Boolean PCRE result; invalid patterns/evaluation errors throw |
| `nullable_string(data, field, max)` | Trimmed optional UTF-8 string; missing/null/blank returns null |

Invalid bounds, wrong optional-string types or overlong values throw `InvalidArgumentException`. Length counts code points, not bytes or user-perceived grapheme clusters. JSON uses PHP's default depth limit of 512. The application owns error accumulation and response generation.

`$utilities->arrays->to_key_value(rows, key_field, value_field)` accepts iterable records and projects them into a key/value map. Missing fields are skipped, explicit null **values** are retained, and the last duplicate key wins. Keys must be integers or strings; PHP's normal numeric-string key coercion applies. Unsupported key types throw `InvalidArgumentException`.

## HTTP helpers

These helpers receive explicit values rather than reading Flight or superglobals.

```php
$http = $utilities->http;
$data = $http->json_object('{"name":"Alan"}');
$page = $http->pagination(page: 3, per_page: 25); // offset 50
$frame = $http->sse_event('updated', ['id' => 123]);

// Application-owned response delivery:
// header('Content-Type: text/event-stream');
// echo $frame;
```

- `json_object(body)` requires a top-level JSON object. Lists and scalars throw `InvalidArgumentException`; malformed JSON throws `JsonException`. Top-level members become an array, while nested objects remain `stdClass` values. Numeric object keys may become integer PHP array keys.
- `pagination(page = 1, per_page = 25, maximum = 100)` returns `{page, per_page, offset}` with page clamped to at least one and size clamped to 1..maximum. Invalid maximums and integer-overflowing offsets throw `InvalidArgumentException`.
- `sse_event(event, payload)` returns a complete frame. Empty event names and CR/LF/NUL injection throw `InvalidArgumentException`; JSON encoding errors throw `JsonException`. It does not send headers, flush buffers or start streaming.
- `set_cookie(name, value, expires = 0, path = '/', secure = true, http_only = true, same_site = 'Strict')` queues a PHP cookie before response output. Expiry uses absolute Unix seconds (zero means session). SameSite is `Strict`, `Lax` or `None`; `None` requires `secure = true`. Bad configuration throws `InvalidArgumentException`; PHP can throw `ValueError` for invalid cookie input. Already-sent headers or a failed write throw `RuntimeException`. Cookies do not modify `$_COOKIE` in the current request.

Authorization, input policies, headers, logging, response delivery and database work remain application responsibilities.

## Development and verification

```sh
composer validate --strict
composer check
```

`composer check` runs PHPStan level 8 with PHP 8.1 targeting, followed by PHPUnit. If a restricted environment blocks PHPStan's local worker sockets, run `vendor/bin/phpstan analyse --no-progress --debug` to disable parallel workers, followed by `composer test`.

Coverage includes strict dates, leap years, DST, UTC/offset handling, pre-epoch timestamps, immutable arithmetic, RFC/DCE UUID layouts and versions, binary UUID storage, random input boundaries, RFC 4648 vectors, malformed encodings, JSON/SSE failure paths, pagination overflow, Unicode validation. The CI workflow targets PHP 8.1 and 8.5; local execution on a different version is not proof that those CI jobs have run.

MIT licensed; see [LICENCE](LICENCE).
