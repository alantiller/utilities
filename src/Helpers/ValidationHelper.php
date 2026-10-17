<?php declare(strict_types=1);

namespace Alantiller\Utilities\Helpers;

use InvalidArgumentException;

/** Stateless validation results; the application chooses how to report failures. */
final class ValidationHelper
{
    /**
     * Collect required-field errors while allowing zero and false as present values.
     * @param array<string, mixed> $data Values indexed by field name.
     * @param list<string> $required Required field names.
     * @return array<string, list<string>> Errors for absent, null or blank-string fields.
     */
    public function required_fields(array $data, array $required): array
    {
        $errors = [];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null
                || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $errors[$field] = ['This field is required.'];
            }
        }
        return $errors;
    }

    /**
     * Test PHP's email syntax validation without asserting deliverability.
     * @param string $value Email address text.
     * @return bool Whether PHP accepts the email syntax.
     */
    public function is_email(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check Unicode code-point length, not bytes or grapheme clusters.
     * @param string $value UTF-8 string.
     * @param int $min Inclusive minimum character count.
     * @param int $max Inclusive maximum character count.
     * @return bool False for invalid UTF-8 or an out-of-range length.
     * @throws InvalidArgumentException If bounds are negative or reversed.
     */
    public function has_length(string $value, int $min = 0, int $max = PHP_INT_MAX): bool
    {
        if ($min < 0 || $max < $min) {
            throw new InvalidArgumentException('Length bounds must be nonnegative and ordered.');
        }
        return mb_check_encoding($value, 'UTF-8') && mb_strlen($value, 'UTF-8') >= $min && mb_strlen($value, 'UTF-8') <= $max;
    }

    /**
     * Test JSON syntax, accepting any JSON value including null and scalars.
     * @param string $value JSON text.
     * @return bool Whether input is valid JSON at PHP's default depth limit (512).
     */
    public function is_json(string $value): bool
    {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Match a caller-supplied PCRE pattern, distinguishing invalid patterns.
     * @param string $value Input text.
     * @param string $pattern Delimited PCRE pattern.
     * @return bool Whether the pattern matches.
     * @throws InvalidArgumentException If PCRE cannot evaluate the pattern.
     */
    public function matches(string $value, string $pattern): bool
    {
        $result = @preg_match($pattern, $value);
        if ($result === false) {
            throw new InvalidArgumentException('Pattern evaluation failed: ' . preg_last_error_msg());
        }
        return $result === 1;
    }

    /**
     * Read an optional trimmed UTF-8 string with a character limit.
     * @param array<string, mixed> $data Input fields.
     * @param string $field Field name to read.
     * @param int $max Maximum Unicode code points after trimming.
     * @return string|null Null for missing, null or blank input; otherwise trimmed text.
     * @throws InvalidArgumentException If the limit, value type, encoding or length is invalid.
     */
    public function nullable_string(array $data, string $field, int $max): ?string
    {
        if ($max < 0) {
            throw new InvalidArgumentException('Maximum length must not be negative.');
        }
        if (!isset($data[$field])) {
            return null;
        }
        $value = $data[$field];
        if (!is_string($value) || !$this->has_length(trim($value), 0, $max)) {
            throw new InvalidArgumentException('Expected a valid UTF-8 string within the length limit: ' . $field);
        }
        $value = trim($value);
        return $value === '' ? null : $value;
    }
}
