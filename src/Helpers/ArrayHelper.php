<?php declare(strict_types=1);

namespace Alantiller\Utilities\Helpers;

use InvalidArgumentException;

/** Array projections that preserve explicit null values. */
final class ArrayHelper
{
    /**
     * Project rows into key/value pairs, with the last duplicate key winning.
     * Rows missing either field are skipped. Null values are retained; PHP's
     * normal integer/numeric-string array-key coercion applies.
     * @param iterable<array<string, mixed>> $rows Input records.
     * @param string $key_field Field containing an integer or string key.
     * @param string $value_field Field containing the output value.
     * @return array<int|string, mixed> Projected values indexed by the selected key.
     * @throws InvalidArgumentException If a present key is neither integer nor string.
     */
    public function to_key_value(iterable $rows, string $key_field, string $value_field): array
    {
        $result = [];
        foreach ($rows as $row) {
            if (!array_key_exists($key_field, $row) || !array_key_exists($value_field, $row)) {
                continue;
            }
            $key = $row[$key_field];
            if (!is_int($key) && !is_string($key)) {
                throw new InvalidArgumentException('Projection keys must be integers or strings.');
            }
            $result[$key] = $row[$value_field];
        }
        return $result;
    }
}
