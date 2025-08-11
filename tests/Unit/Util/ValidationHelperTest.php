<?php

declare(strict_types=1);

namespace App\Tests\Unit\Util;

use App\Util\ValidationHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ValidationHelper utility class.
 *
 * Tests validation methods to ensure proper error handling
 * and consistent validation behavior across the application.
 */
final class ValidationHelperTest extends TestCase
{
    public function testValidateNotEmptyWithValidString(): void
    {
        // Should not throw exception
        ValidationHelper::validateNotEmpty('valid string');
        $this->assertTrue(true); // Assert test passed
    }

    public function testValidateNotEmptyWithEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value cannot be empty');

        ValidationHelper::validateNotEmpty('');
    }

    public function testValidateNotEmptyWithWhitespaceString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Field name cannot be empty');

        ValidationHelper::validateNotEmpty('   ', 'field name');
    }

    public function testValidateRangeWithValidValue(): void
    {
        // Should not throw exception
        ValidationHelper::validateRange(0.5, 0.0, 1.0);
        $this->assertTrue(true); // Assert test passed
    }

    public function testValidateRangeWithValueBelowMin(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Score must be between 0.0 and 1.0, got: -0.1');

        ValidationHelper::validateRange(-0.1, 0.0, 1.0, 'score');
    }

    public function testValidateRangeWithValueAboveMax(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be between 0.0 and 1.0, got: 1.5');

        ValidationHelper::validateRange(1.5, 0.0, 1.0);
    }

    public function testValidateSafeStringWithSafeContent(): void
    {
        // Should not throw exception
        ValidationHelper::validateSafeString('Safe content 123');
        $this->assertTrue(true); // Assert test passed
    }

    public function testValidateSafeStringWithDangerousContent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Input contains invalid characters');

        ValidationHelper::validateSafeString('<script>alert("xss")</script>', 'input');
    }

    public function testValidateMaxLengthWithValidString(): void
    {
        // Should not throw exception
        ValidationHelper::validateMaxLength('Short', 10);
        $this->assertTrue(true); // Assert test passed
    }

    public function testValidateMaxLengthWithTooLongString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Text is too long (max 5 characters, got 10)');

        ValidationHelper::validateMaxLength('Too long text', 5, 'text');
    }

    public function testNormalizeWhitespace(): void
    {
        $this->assertSame('test string', ValidationHelper::normalizeWhitespace('  test   string  '));
        $this->assertSame('single', ValidationHelper::normalizeWhitespace('single'));
        $this->assertSame('', ValidationHelper::normalizeWhitespace('   '));
    }

    public function testSanitizeString(): void
    {
        $this->assertSame('clean text', ValidationHelper::sanitizeString('  clean   text  '));
        $this->assertSame('scriptalert(xss)/script', ValidationHelper::sanitizeString('<script>alert("xss")</script>'));
        $this->assertSame('safe content', ValidationHelper::sanitizeString('safe content'));
    }
}