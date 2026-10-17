<?php declare(strict_types=1);

namespace Alantiller\Utilities\Tests;

use Alantiller\Utilities\Helpers\UuidHelper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Verify native UUID layouts, published vectors and failure boundaries. */
final class UuidHelperTest extends TestCase
{
    /** Verify the RFC 4122 Appendix B DNS/name v3 vector. */
    public function test_v3_standard_vector(): void
    {
        $uuids = new UuidHelper();
        self::assertSame('3d813cbb-47fb-32ba-91df-831e1593ac29', $uuids->v3(UuidHelper::NAMESPACE_DNS, 'www.widgets.com'));
        self::assertNotSame($uuids->v3(UuidHelper::NAMESPACE_DNS, 'a'), $uuids->v3(UuidHelper::NAMESPACE_URL, 'a'));
    }

    /** Decode the v1 timestamp fields independently and verify epoch and sequence layout. */
    public function test_v1_timestamp_node_and_sequence(): void
    {
        $uuids = new UuidHelper();
        $before = microtime(true);
        $uuid = $uuids->v1('010203040506', 0x1234);
        $after = microtime(true);
        self::assertTrue($uuids->is_valid($uuid, 1));
        self::assertSame('9234', substr($uuid, 19, 4));
        self::assertSame('010203040506', substr($uuid, 24));
        $ticks = (hexdec(substr($uuid, 15, 3)) << 48) | (hexdec(substr($uuid, 9, 4)) << 32) | hexdec(substr($uuid, 0, 8));
        $unix = ($ticks - 122192928000000000) / 10000000;
        self::assertGreaterThanOrEqual($before - 0.00001, $unix);
        self::assertLessThanOrEqual($after + 0.00001, $unix);
    }

    /** Verify DCE field replacement, maximum identifier, domain and 6-bit sequence. */
    public function test_v2_dce_fields(): void
    {
        $uuids = new UuidHelper();
        $uuid = $uuids->v2(4294967295, UuidHelper::DOMAIN_ORG, '010203040506', 63);
        self::assertTrue($uuids->is_valid($uuid, 2));
        self::assertSame('ffffffff', substr($uuid, 0, 8));
        self::assertSame('bf02', substr($uuid, 19, 4));
        self::assertSame('010203040506', substr($uuid, 24));
        self::assertSame('8000', substr($uuids->v2(0, 0, '010203040506', 0), 19, 4));
    }

    /** Verify default time UUIDs use multicast random nodes and correct version/variant bits. */
    public function test_random_node_and_binary_round_trips(): void
    {
        $uuids = new UuidHelper();
        foreach ([$uuids->v1(), $uuids->v2(1001), $uuids->v3(UuidHelper::NAMESPACE_DNS, 'example.org'), $uuids->v4()] as $index => $uuid) {
            self::assertSame($index + 1, $uuids->version($uuid));
            self::assertSame(16, strlen($uuids->to_bytes($uuid)));
            self::assertSame($uuid, $uuids->from_bytes($uuids->to_bytes(strtoupper($uuid))));
            if ($index < 2) {
                self::assertSame(1, hexdec(substr($uuid, 24, 2)) & 1);
            }
        }
    }

    /** Verify random batch sizes, empty batches and short labels. */
    public function test_random_batches(): void
    {
        $uuids = new UuidHelper();
        $batch = $uuids->generate_multiple(1000);
        self::assertCount(1000, array_unique($batch));
        foreach ($batch as $uuid) {
            self::assertTrue($uuids->is_valid($uuid, 4));
        }
        self::assertSame([], $uuids->generate_multiple(0));
        self::assertMatchesRegularExpression('/^[a-f0-9]{8}$/', $uuids->short_id());
        self::assertSame(32, strlen($uuids->short_id(32)));
    }

    /**
     * Supply unsupported UUID forms and variants.
     * @return iterable<array{string}> Invalid text cases.
     */
    public static function invalid_uuids(): iterable
    {
        yield ['00000000-0000-0000-0000-000000000000'];
        yield ['3d813cbb-47fb-32ba-71df-831e1593ac29'];
        yield ['3d813cbb-47fb-52ba-91df-831e1593ac29'];
        yield ["3d813cbb-47fb-32ba-91df-831e1593ac29\n"];
        yield ['urn:uuid:3d813cbb-47fb-32ba-91df-831e1593ac29'];
        yield [''];
    }

    /**
     * Reject noncanonical or unsupported UUIDs.
     * @param string $value Invalid UUID input.
     */
    #[DataProvider('invalid_uuids')]
    public function test_invalid_uuid(string $value): void
    {
        $uuids = new UuidHelper();
        self::assertFalse($uuids->is_valid($value));
        $this->expectException(InvalidArgumentException::class);
        $uuids->normalize($value);
    }

    /**
     * Supply invalid parameter combinations for generator and byte boundaries.
     * @return iterable<string, array{string, list<int|string>}> Method name and invalid arguments.
     */
    public static function invalid_arguments(): iterable
    {
        yield 'node' => ['v1', ['not-hex']];
        yield 'v1 sequence negative' => ['v1', ['010203040506', -1]];
        yield 'v1 sequence overflow' => ['v1', ['010203040506', 16384]];
        yield 'v2 ID negative' => ['v2', [-1]];
        yield 'v2 ID overflow' => ['v2', [4294967296]];
        yield 'v2 domain' => ['v2', [1, 3]];
        yield 'v2 sequence' => ['v2', [1, 0, '010203040506', 64]];
        yield 'binary size' => ['from_bytes', ['short']];
        yield 'negative count' => ['generate_multiple', [-1]];
        yield 'short ID size' => ['short_id', [0]];
    }

    /**
     * Verify invalid generator inputs raise explicit argument errors.
     * @param string $method UUID helper method to exercise.
     * @param list<int|string> $arguments Invalid positional arguments.
     */
    #[DataProvider('invalid_arguments')]
    public function test_invalid_arguments(string $method, array $arguments): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new UuidHelper())->{$method}(...$arguments);
    }
}
