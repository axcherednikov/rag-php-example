<?php

declare(strict_types=1);

namespace App\Tests\Unit\ValueObject;

use App\ValueObject\SearchQuery;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SearchQuery value object.
 *
 * Tests validation, normalization, and behavior of search queries
 * to ensure proper encapsulation and data integrity.
 */
final class SearchQueryTest extends TestCase
{
    public function testCanCreateFromValidInput(): void
    {
        $query = SearchQuery::fromUserInput('test query');

        $this->assertSame('test query', $query->toString());
        $this->assertFalse($query->isEmpty());
        $this->assertSame(10, $query->getLength());
    }

    public function testNormalizesWhitespace(): void
    {
        $query = SearchQuery::fromUserInput('  test   query  ');

        $this->assertSame('test query', $query->toString());
    }

    public function testThrowsExceptionForEmptyQuery(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Search query cannot be empty');

        new SearchQuery('');
    }

    public function testThrowsExceptionForTooLongQuery(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Search query is too long');

        new SearchQuery(str_repeat('a', 1001));
    }

    public function testThrowsExceptionForDangerousCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Search query contains invalid characters');

        new SearchQuery('test<script>alert("xss")</script>');
    }

    public function testContainsMethod(): void
    {
        $query = new SearchQuery('AMD Ryzen processor');

        $this->assertTrue($query->contains('AMD'));
        $this->assertTrue($query->contains('processor'));
        $this->assertTrue($query->contains('amd')); // Case insensitive
        $this->assertFalse($query->contains('Intel'));
    }

    public function testStringRepresentation(): void
    {
        $query = new SearchQuery('test query');

        $this->assertSame('test query', (string) $query);
        $this->assertSame('test query', $query->toString());
    }
}