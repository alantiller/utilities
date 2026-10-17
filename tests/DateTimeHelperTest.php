<?php declare(strict_types=1);

namespace Alantiller\Utilities\Tests;

use Alantiller\Utilities\Helpers\DateTimeHelper;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Verify calendar validation, timezone conversions and immutable arithmetic. */
final class DateTimeHelperTest extends TestCase
{
    /** Verify offsets, DST and explicit source zones without changing global state. */
    public function test_timezone_conversion(): void
    {
        $global = date_default_timezone_get();
        $dates = new DateTimeHelper('Europe/London');
        self::assertSame('2024-07-01 11:00:00', $dates->to_utc('2024-07-01 12:00:00'));
        self::assertSame('2024-01-01 12:00:00', $dates->to_utc('2024-01-01 12:00:00'));
        self::assertSame('2024-07-01 10:00:00', $dates->to_utc('2024-07-01T12:00:00+02:00'));
        self::assertSame('2024-07-01 16:00:00', $dates->to_utc('2024-07-01 12:00:00', 'America/New_York'));
        self::assertSame($global, date_default_timezone_get());
    }

    /** Verify strict formats reset unspecified fields, accept leap days and retain microseconds. */
    public function test_strict_formats_and_fractional_seconds(): void
    {
        $dates = new DateTimeHelper();
        self::assertSame('2024-02-29 00:00:00', $dates->convert('29/02/2024', null, null, 'd/m/Y'));
        self::assertSame('2024-02-29T12:34:56.123456+00:00', $dates->to_iso8601('2024-02-29T13:34:56.123456+01:00'));
        self::assertSame('1970-01-01 12:34:00', $dates->convert('12:34', null, null, 'H:i'));
    }

    /**
     * Supply malformed calendar and format examples.
     * @return iterable<string, array{string, string|null}> Invalid input and optional input format.
     */
    public static function invalid_dates(): iterable
    {
        yield 'blank' => ['', null];
        yield 'nonsense' => ['not-a-date', null];
        yield 'non-leap day' => ['2023-02-29', 'Y-m-d'];
        yield 'overflow' => ['2024-02-30', null];
        yield 'format mismatch' => ['2024/02/29', 'Y-m-d'];
        yield 'trailing input' => ['2024-02-29 garbage', 'Y-m-d'];
        yield 'null byte' => ["2024-01-01\0", 'Y-m-d'];
    }

    /**
     * Ensure invalid dates fail explicitly rather than roll over silently.
     * @param string $value Invalid date text.
     * @param string|null $format Optional strict parsing format.
     */
    #[DataProvider('invalid_dates')]
    public function test_invalid_dates(string $value, ?string $format): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new DateTimeHelper())->parse($value, $format);
    }

    /** Verify invalid configuration fails during construction. */
    public function test_invalid_timezone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DateTimeHelper('not/a/timezone');
    }

    /** Verify epoch and pre-epoch timestamps round-trip across zones. */
    public function test_unix_seconds(): void
    {
        $dates = new DateTimeHelper();
        self::assertSame('1970-01-01 00:00:00', $dates->from_timestamp(0));
        self::assertSame(-1, $dates->to_timestamp($dates->from_timestamp(-1)));
        self::assertSame('1970-01-01 01:00:00', $dates->from_timestamp(0, null, 'Europe/Paris'));
    }

    /** Verify supplied mutable dates are untouched and DST distinguishes calendar from elapsed time. */
    public function test_immutable_calendar_arithmetic(): void
    {
        $dates = new DateTimeHelper('Europe/London');
        $input = new DateTime('2024-03-30T12:00:00+00:00');
        $result = $dates->add_interval($input, 'P1D');
        self::assertSame('2024-03-30', $input->format('Y-m-d'));
        self::assertSame('2024-03-31', $result->format('Y-m-d'));
        self::assertSame(82800, $dates->difference_seconds('2024-03-30 12:00:00', '2024-03-31 12:00:00'));
        self::assertSame('2024-03-31 12:00:00', $dates->add_interval('2024-03-30 12:00:00', 'P1D')->format('Y-m-d H:i:s'));
        self::assertSame('2024-03-29', $dates->add_interval($input, 'P1D', true)->format('Y-m-d'));
    }

    /** Verify malformed ISO durations fail explicitly. */
    public function test_invalid_interval(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new DateTimeHelper())->add_interval('2024-01-01', 'bad');
    }

    /** Verify deterministic relative phrases for past, present and future instants. */
    public function test_relative_time(): void
    {
        $dates = new DateTimeHelper();
        self::assertSame('1 minute ago', $dates->time_ago('@0', '@60'));
        self::assertSame('in 2 hours', $dates->time_ago('@7200', '@0'));
        self::assertSame('just now', $dates->time_ago('@0', '@59'));
    }
}
