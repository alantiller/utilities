<?php declare(strict_types=1);

namespace Alantiller\Utilities\Helpers;

use InvalidArgumentException;
use JsonException;
use RuntimeException;
use stdClass;

/** Explicit HTTP data and output helpers with no framework or database dependency. */
final class HttpHelper
{
    /**
     * Decode a JSON object, rejecting lists/scalars without losing numeric object keys.
     * @param string $body Raw JSON request body.
     * @return array<string|int, mixed> Object members; nested objects remain stdClass values.
     * @throws JsonException If JSON syntax is invalid or exceeds depth 512.
     * @throws InvalidArgumentException If the top-level JSON value is not an object.
     */
    public function json_object(string $body): array
    {
        $data = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        if (!$data instanceof stdClass) {
            throw new InvalidArgumentException('The request body must be a JSON object.');
        }
        return get_object_vars($data);
    }

    /**
     * Calculate bounded pagination from explicit page values.
     * @param int $page Requested one-based page; clamped to at least one.
     * @param int $per_page Requested page size, clamped to 1..maximum.
     * @param int $maximum Maximum permitted page size, at least one.
     * @return array{page: int, per_page: int, offset: int} Normalized pagination.
     * @throws InvalidArgumentException If maximum is invalid or offset overflows an integer.
     */
    public function pagination(int $page = 1, int $per_page = 25, int $maximum = 100): array
    {
        if ($maximum < 1) {
            throw new InvalidArgumentException('Maximum page size must be positive.');
        }
        $page = max(1, $page);
        $per_page = min($maximum, max(1, $per_page));
        if ($page - 1 > intdiv(PHP_INT_MAX, $per_page)) {
            throw new InvalidArgumentException('Pagination offset exceeds the integer range.');
        }
        return ['page' => $page, 'per_page' => $per_page, 'offset' => ($page - 1) * $per_page];
    }

    /**
     * Build one SSE event without sending headers or flushing application buffers.
     * @param string $event Nonempty event name without carriage returns, newlines or NUL.
     * @param array<array-key, mixed> $payload JSON-serializable event data.
     * @return string Complete event frame ending in two newlines.
     * @throws InvalidArgumentException If event name permits frame injection.
     * @throws JsonException If the payload cannot be encoded.
     */
    public function sse_event(string $event, array $payload): string
    {
        if ($event === '' || strpbrk($event, "\r\n\0") !== false) {
            throw new InvalidArgumentException('Invalid SSE event name.');
        }
        return 'event: ' . $event . "\n" . 'data: ' . json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n\n";
    }

    /**
     * Send a cookie through PHP; the caller must invoke this before response output.
     * @param string $name Cookie name accepted by PHP.
     * @param string $value Cookie value; PHP handles encoding.
     * @param int $expires Absolute Unix expiry seconds; zero is a session cookie.
     * @param string $path Cookie path.
     * @param bool $secure Require HTTPS; required for SameSite=None.
     * @param bool $http_only Hide from JavaScript.
     * @param string $same_site Strict, Lax or None.
     * @return void No application state or cookie superglobals are changed.
     * @throws InvalidArgumentException If SameSite configuration is invalid.
     * @throws \ValueError If PHP rejects the cookie name or options.
     * @throws RuntimeException If headers have already been sent or PHP cannot queue the cookie.
     */
    public function set_cookie(string $name, string $value, int $expires = 0, string $path = '/', bool $secure = true, bool $http_only = true, string $same_site = 'Strict'): void
    {
        if (!in_array($same_site, ['Strict', 'Lax', 'None'], true) || ($same_site === 'None' && !$secure)) {
            throw new InvalidArgumentException('Invalid SameSite cookie configuration.');
        }
        if (headers_sent() || !setcookie($name, $value, ['expires' => $expires, 'path' => $path, 'secure' => $secure, 'httponly' => $http_only, 'samesite' => $same_site])) {
            throw new RuntimeException('Unable to queue cookie headers.');
        }
    }
}
