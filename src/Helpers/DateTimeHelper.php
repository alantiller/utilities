<?php declare(strict_types=1);

namespace Alantiller\Utilities\Helpers;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use Throwable;

/** Immutable date parsing and conversion; never changes PHP's global timezone. */
final class DateTimeHelper
{
    private DateTimeZone $timezone;

    /**
     * Set defaults used when input does not contain a timezone.
     * @param string $timezone PHP timezone identifier or UTC offset.
     * @param string $format Default PHP output format.
     * @throws InvalidArgumentException If the timezone is invalid.
     */
    public function __construct(string $timezone = 'UTC', private string $format = 'Y-m-d H:i:s')
    {
        $this->timezone = $this->zone($timezone);
    }

    /**
     * Resolve a timezone without exposing PHP-version-specific exceptions.
     * @param string $timezone PHP timezone identifier or UTC offset.
     * @return DateTimeZone Validated timezone.
     * @throws InvalidArgumentException If PHP cannot resolve the timezone.
     */
    private function zone(string $timezone): DateTimeZone
    {
        try {
            return new DateTimeZone($timezone);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Invalid timezone: ' . $timezone, 0, $exception);
        }
    }

    /**
     * Parse a date without mutating supplied date objects.
     * Explicit input offsets take precedence over the source timezone. An input
     * format enables strict calendar validation and resets unspecified fields to
     * the Unix epoch. Free-form parsing supports PHP relative date expressions.
     * @param string|DateTimeInterface $value Nonblank date text or existing instant.
     * @param string|null $input_format PHP parsing format, or null for free-form text.
     * @param string|null $source_timezone Zone for offset-free input; null uses the default.
     * @return DateTimeImmutable Parsed date with its input/source timezone.
     * @throws InvalidArgumentException If the date, calendar fields or timezone are invalid.
     */
    public function parse(string|DateTimeInterface $value, ?string $input_format = null, ?string $source_timezone = null): DateTimeImmutable
    {
        $zone = $source_timezone === null ? $this->timezone : $this->zone($source_timezone);
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }
        if (trim($value) === '') {
            throw new InvalidArgumentException('Date must not be blank.');
        }
        try {
            $date = $input_format === null
                ? new DateTimeImmutable($value, $zone)
                : DateTimeImmutable::createFromFormat('!' . $input_format, $value, $zone);
            $errors = DateTimeImmutable::getLastErrors();
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Invalid date or input format.', 0, $exception);
        }
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new InvalidArgumentException('Invalid date or input format.');
        }
        return $date;
    }

    /**
     * Format the current instant in a selected timezone.
     * @param string|null $format PHP output format; null uses the configured format.
     * @param string|null $timezone Output timezone; null uses the configured timezone.
     * @return string Formatted current time.
     * @throws InvalidArgumentException If the timezone is invalid.
     */
    public function now(?string $format = null, ?string $timezone = null): string
    {
        return $this->convert('now', $format, $timezone);
    }

    /**
     * Convert an instant between input/output formats and timezones.
     * @param string|DateTimeInterface $value Input date or instant.
     * @param string|null $output_format PHP output format; null uses the configured format.
     * @param string|null $target_timezone Output timezone; null uses the configured timezone.
     * @param string|null $input_format Strict PHP input format, or null for free-form parsing.
     * @param string|null $source_timezone Timezone for input without an explicit offset.
     * @return string Converted date text.
     * @throws InvalidArgumentException If the date, input format or timezone is invalid.
     */
    public function convert(string|DateTimeInterface $value, ?string $output_format = null, ?string $target_timezone = null, ?string $input_format = null, ?string $source_timezone = null): string
    {
        $zone = $target_timezone === null ? $this->timezone : $this->zone($target_timezone);
        return $this->parse($value, $input_format, $source_timezone)->setTimezone($zone)->format($output_format ?? $this->format);
    }

    /**
     * Format an instant as a UTC database date, discarding subsecond precision.
     * @param string|DateTimeInterface $value Input instant.
     * @param string|null $source_timezone Zone used for input without an offset.
     * @return string UTC date in Y-m-d H:i:s format.
     * @throws InvalidArgumentException If the date or timezone is invalid.
     */
    public function to_utc(string|DateTimeInterface $value, ?string $source_timezone = null): string
    {
        return $this->convert($value, 'Y-m-d H:i:s', 'UTC', null, $source_timezone);
    }

    /**
     * Produce an ISO-8601 timestamp with microseconds and an explicit offset.
     * @param string|DateTimeInterface $value Input instant.
     * @param string|null $timezone Output zone; null uses the configured timezone.
     * @return string ISO-8601 timestamp, including six fractional digits.
     * @throws InvalidArgumentException If the input or timezone is invalid.
     */
    public function to_iso8601(string|DateTimeInterface $value, ?string $timezone = null): string
    {
        return $this->convert($value, 'Y-m-d\TH:i:s.uP', $timezone);
    }

    /**
     * Extract whole Unix seconds; fractional seconds are discarded.
     * @param string|DateTimeInterface $value Input instant.
     * @param string|null $source_timezone Zone for offset-free text.
     * @return int Signed Unix timestamp in seconds.
     * @throws InvalidArgumentException If the input or timezone is invalid.
     */
    public function to_timestamp(string|DateTimeInterface $value, ?string $source_timezone = null): int
    {
        return $this->parse($value, null, $source_timezone)->getTimestamp();
    }

    /**
     * Format signed Unix seconds, including dates before 1970.
     * @param int $timestamp Unix seconds, never milliseconds.
     * @param string|null $format Output format; null uses the default.
     * @param string|null $timezone Output timezone; null uses the default.
     * @return string Formatted instant.
     * @throws InvalidArgumentException If the timezone is invalid.
     */
    public function from_timestamp(int $timestamp, ?string $format = null, ?string $timezone = null): string
    {
        return $this->convert('@' . $timestamp, $format, $timezone);
    }

    /**
     * Add or subtract a calendar interval without mutating the input.
     * PHP calendar arithmetic applies, including month overflow and DST changes.
     * @param string|DateTimeInterface $value Starting date.
     * @param string $interval ISO-8601 duration such as P1D or PT30M; no leading minus.
     * @param bool $subtract Whether to subtract instead of add.
     * @return DateTimeImmutable Adjusted date in the input timezone.
     * @throws InvalidArgumentException If the date or duration is invalid.
     */
    public function add_interval(string|DateTimeInterface $value, string $interval, bool $subtract = false): DateTimeImmutable
    {
        $date = $this->parse($value);
        try {
            $duration = new DateInterval($interval);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Invalid ISO-8601 interval.', 0, $exception);
        }
        return $subtract ? $date->sub($duration) : $date->add($duration);
    }

    /**
     * Compare two instants as a signed count of elapsed seconds, independent of DST.
     * @param string|DateTimeInterface $from Start instant.
     * @param string|DateTimeInterface $to End instant.
     * @return int End minus start in whole seconds; negative when end precedes start.
     * @throws InvalidArgumentException If either date is invalid.
     */
    public function difference_seconds(string|DateTimeInterface $from, string|DateTimeInterface $to): int
    {
        return $this->to_timestamp($to) - $this->to_timestamp($from);
    }

    /**
     * Describe elapsed or future time using approximate English units.
     * @param string|DateTimeInterface $value Instant to describe.
     * @param string|DateTimeInterface $reference Reference instant; defaults to now.
     * @return string Relative time; months are 30 days and years are 365 days.
     * @throws InvalidArgumentException If either date is invalid.
     */
    public function time_ago(string|DateTimeInterface $value, string|DateTimeInterface $reference = 'now'): string
    {
        $seconds = $this->difference_seconds($value, $reference);
        $absolute = abs($seconds);
        if ($absolute < 60) {
            return 'just now';
        }
        foreach ([31536000 => 'year', 2592000 => 'month', 604800 => 'week', 86400 => 'day', 3600 => 'hour', 60 => 'minute'] as $size => $unit) {
            if ($absolute >= $size) {
                $count = (int) floor($absolute / $size);
                $description = $count . ' ' . $unit . ($count === 1 ? '' : 's');
                return $seconds < 0 ? 'in ' . $description : $description . ' ago';
            }
        }
        return 'just now';
    }
}
