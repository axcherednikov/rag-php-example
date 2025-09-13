<?php

declare(strict_types=1);

namespace App\Util;

/**
 * Utility class for common array operations.
 *
 * Provides reusable array manipulation methods to eliminate
 * code duplication across the application.
 */
final class ArrayHelper
{
    /**
     * Extract unique values from array of objects by property.
     *
     * @param array<mixed> $objects  Array of objects
     * @param string       $property Property name to extract
     *
     * @return array<mixed> Unique values
     */
    public static function extractUniqueProperty(array $objects, string $property): array
    {
        $values = array_map(
            fn ($object) => is_array($object) ? $object[$property] ?? null : $object->$property ?? null,
            $objects
        );

        return array_unique(array_filter($values, fn ($value) => null !== $value));
    }

    /**
     * Group array elements by property value.
     *
     * @param array<mixed> $objects  Array of objects
     * @param string       $property Property name to group by
     *
     * @return array<string, array<mixed>> Grouped array
     */
    public static function groupByProperty(array $objects, string $property): array
    {
        $groups = [];

        foreach ($objects as $object) {
            $key = is_array($object) ? $object[$property] ?? 'unknown' : $object->$property ?? 'unknown';
            $groups[$key][] = $object;
        }

        return $groups;
    }

    /**
     * Find the first element that matches a condition.
     *
     * @param array<mixed> $array    Array to search
     * @param callable     $callback Condition callback
     *
     * @return mixed|null First matching element or null
     */
    public static function findFirst(array $array, callable $callback): mixed
    {
        foreach ($array as $element) {
            if ($callback($element)) {
                return $element;
            }
        }

        return null;
    }

    /**
     * Check if array contains element matching condition.
     *
     * @param array<mixed> $array    Array to search
     * @param callable     $callback Condition callback
     *
     * @return bool True if matching element found
     */
    public static function contains(array $array, callable $callback): bool
    {
        return null !== self::findFirst($array, $callback);
    }

    /**
     * Get nested array value safely.
     *
     * @param array<mixed> $array   Source array
     * @param string       $path    Dot-notation path (e.g., 'payload.name')
     * @param mixed        $default Default value if path not found
     *
     * @return mixed Value at path or default
     */
    public static function getNestedValue(array $array, string $path, mixed $default = null): mixed
    {
        $keys = explode('.', $path);
        $current = $array;

        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return $default;
            }
            $current = $current[$key];
        }

        return $current;
    }

    /**
     * Convert array to query string safely.
     *
     * @param array<mixed> $array Input array
     *
     * @return string URL-encoded query string
     */
    public static function toQueryString(array $array): string
    {
        return http_build_query($array, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Merge arrays recursively with proper handling of numeric keys.
     *
     * @param array<mixed> ...$arrays Arrays to merge
     *
     * @return array<mixed> Merged array
     */
    public static function mergeRecursive(array ...$arrays): array
    {
        $result = [];

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (is_int($key)) {
                    $result[] = $value;
                } elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                    $result[$key] = self::mergeRecursive($result[$key], $value);
                } else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Get array statistics (min, max, average, count).
     *
     * @param array<float|int> $numbers Array of numeric values
     *
     * @return array<string, float|int|null> Statistics array
     */
    public static function getNumericStats(array $numbers): array
    {
        if ([] === $numbers) {
            return [
                'count' => 0,
                'min' => null,
                'max' => null,
                'average' => null,
                'sum' => null,
            ];
        }

        $numbers = array_filter($numbers, 'is_numeric');

        if ([] === $numbers) {
            return [
                'count' => 0,
                'min' => null,
                'max' => null,
                'average' => null,
                'sum' => null,
            ];
        }

        $sum = array_sum($numbers);
        $count = count($numbers);

        return [
            'count' => $count,
            'min' => min($numbers),
            'max' => max($numbers),
            'average' => $sum / $count,
            'sum' => $sum,
        ];
    }
}
