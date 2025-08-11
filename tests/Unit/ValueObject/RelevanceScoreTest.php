<?php

declare(strict_types=1);

namespace App\Tests\Unit\ValueObject;

use App\ValueObject\RelevanceScore;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RelevanceScore value object.
 *
 * Tests score validation, percentage conversion, and relevance classification
 * to ensure proper behavior of similarity scores.
 */
final class RelevanceScoreTest extends TestCase
{
    public function testCanCreateFromValidScore(): void
    {
        $score = RelevanceScore::fromFloat(0.85);

        $this->assertSame(0.85, $score->value);
        $this->assertSame(85.0, $score->toPercentage());
        $this->assertSame('85%', (string) $score);
    }

    public function testThrowsExceptionForNegativeScore(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Relevance score must be between 0.0 and 1.0');

        RelevanceScore::fromFloat(-0.1);
    }

    public function testThrowsExceptionForScoreAboveOne(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Relevance score must be between 0.0 and 1.0');

        RelevanceScore::fromFloat(1.1);
    }

    public function testThrowsExceptionForInfiniteScore(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Relevance score must be a finite number');

        RelevanceScore::fromFloat(INF);
    }

    public function testHighRelevanceClassification(): void
    {
        $highScore = RelevanceScore::fromFloat(0.9);

        $this->assertTrue($highScore->isHighRelevance());
        $this->assertFalse($highScore->isMediumRelevance());
        $this->assertFalse($highScore->isLowRelevance());
        $this->assertSame('Высокая релевантность', $highScore->getDescription());
    }

    public function testMediumRelevanceClassification(): void
    {
        $mediumScore = RelevanceScore::fromFloat(0.7);

        $this->assertFalse($mediumScore->isHighRelevance());
        $this->assertTrue($mediumScore->isMediumRelevance());
        $this->assertFalse($mediumScore->isLowRelevance());
        $this->assertSame('Средняя релевантность', $mediumScore->getDescription());
    }

    public function testLowRelevanceClassification(): void
    {
        $lowScore = RelevanceScore::fromFloat(0.3);

        $this->assertFalse($lowScore->isHighRelevance());
        $this->assertFalse($lowScore->isMediumRelevance());
        $this->assertTrue($lowScore->isLowRelevance());
        $this->assertSame('Низкая релевантность', $lowScore->getDescription());
    }

    public function testThresholdChecking(): void
    {
        $score = RelevanceScore::fromFloat(0.6);

        $this->assertTrue($score->meetsThreshold(0.5));
        $this->assertTrue($score->meetsThreshold(0.6));
        $this->assertFalse($score->meetsThreshold(0.7));
    }

    public function testScoreComparison(): void
    {
        $score1 = RelevanceScore::fromFloat(0.3);
        $score2 = RelevanceScore::fromFloat(0.7);
        $score3 = RelevanceScore::fromFloat(0.7);

        $this->assertSame(-1, $score1->compareTo($score2));
        $this->assertSame(1, $score2->compareTo($score1));
        $this->assertSame(0, $score2->compareTo($score3));
    }
}