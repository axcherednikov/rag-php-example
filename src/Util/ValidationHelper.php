<?php

declare(strict_types=1);

namespace App\Util;

/**
 * Utility class for common validation operations.
 *
 * Centralizes validation logic to eliminate code duplication
 * and ensure consistent validation across the application.
 */
final class ValidationHelper
{
    /**
     * Validate that a string is not empty after trimming.
     *
     * @param string $value     Value to validate
     * @param string $fieldName Field name for error messages
     *
     * @throws \InvalidArgumentException If value is empty
     */
    public static function validateNotEmpty(string $value, string $fieldName = 'value'): void
    {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException(ucfirst($fieldName).' cannot be empty');
        }
    }

    /**
     * Validate that a numeric value is within range.
     *
     * @param float  $value     Value to validate
     * @param float  $min       Minimum allowed value
     * @param float  $max       Maximum allowed value
     * @param string $fieldName Field name for error messages
     *
     * @throws \InvalidArgumentException If value is out of range
     */
    public static function validateRange(float $value, float $min, float $max, string $fieldName = 'value'): void
    {
        if ($value < $min || $value > $max) {
            throw new \InvalidArgumentException(ucfirst($fieldName)." must be between {$min} and {$max}, got: {$value}");
        }
    }

    /**
     * Validate that a string doesn't contain dangerous characters.
     *
     * @param string $value     Value to validate
     * @param string $fieldName Field name for error messages
     *
     * @throws \InvalidArgumentException If value contains dangerous characters
     */
    public static function validateSafeString(string $value, string $fieldName = 'value'): void
    {
        if (preg_match('/[<>"\']/', $value)) {
            throw new \InvalidArgumentException(ucfirst($fieldName).' contains invalid characters');
        }
    }

    /**
     * Validate maximum string length.
     *
     * @param string $value     Value to validate
     * @param int    $maxLength Maximum allowed length
     * @param string $fieldName Field name for error messages
     *
     * @throws \InvalidArgumentException If value is too long
     */
    public static function validateMaxLength(string $value, int $maxLength, string $fieldName = 'value'): void
    {
        $length = mb_strlen($value, 'UTF-8');

        if ($length > $maxLength) {
            throw new \InvalidArgumentException(ucfirst($fieldName)." is too long (max {$maxLength} characters, got {$length})");
        }
    }

    /**
     * Validate that an array is not empty.
     *
     * @param array<mixed> $value     Array to validate
     * @param string       $fieldName Field name for error messages
     *
     * @throws \InvalidArgumentException If array is empty
     */
    public static function validateArrayNotEmpty(array $value, string $fieldName = 'array'): void
    {
        if (empty($value)) {
            throw new \InvalidArgumentException(ucfirst($fieldName).' cannot be empty');
        }
    }

    /**
     * Validate that a value is a finite number.
     *
     * @param float  $value     Value to validate
     * @param string $fieldName Field name for error messages
     *
     * @throws \InvalidArgumentException If value is not finite
     */
    public static function validateFiniteNumber(float $value, string $fieldName = 'value'): void
    {
        if (!is_finite($value)) {
            throw new \InvalidArgumentException(ucfirst($fieldName).' must be a finite number');
        }
    }

    /**
     * Normalize whitespace in a string.
     *
     * @param string $value Input string
     *
     * @return string Normalized string
     */
    public static function normalizeWhitespace(string $value): string
    {
        $normalized = preg_replace('/\s+/', ' ', $value);

        return trim($normalized ?: $value);
    }

    /**
     * Sanitize string for safe usage.
     *
     * @param string $value Input string
     *
     * @return string Sanitized string
     */
    public static function sanitizeString(string $value): string
    {
        // Remove potentially dangerous characters
        $sanitized = preg_replace('/[<>"\']/', '', $value);

        // Normalize whitespace
        return self::normalizeWhitespace($sanitized ?: $value);
    }
}
