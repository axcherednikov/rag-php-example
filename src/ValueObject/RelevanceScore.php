<?php

declare(strict_types=1);

namespace App\ValueObject;

/**
 * Value object representing a relevance score.
 *
 * Encapsulates validation and operations for similarity scores
 * from vector database searches.
 */
final readonly class RelevanceScore
{
    public function __construct(
        public float $value,
    ) {
        $this->validate();
    }

    /**
     * Create from float value.
     *
     * @param float $score Relevance score (0.0 to 1.0)
     *
     * @return self Relevance score value object
     *
     * @throws \InvalidArgumentException If score is out of range
     */
    public static function fromFloat(float $score): self
    {
        return new self($score);
    }

    /**
     * Get score as percentage (0-100).
     *
     * @return float Percentage value
     */
    public function toPercentage(): float
    {
        return round($this->value * 100, 1);
    }

    /**
     * Check if score meets minimum threshold.
     *
     * @param float $threshold Minimum threshold (0.0 to 1.0)
     *
     * @return bool True if score meets threshold
     */
    public function meetsThreshold(float $threshold): bool
    {
        return $this->value >= $threshold;
    }

    /**
     * Check if this is a high relevance score.
     *
     * @return bool True if score is above 0.8
     */
    public function isHighRelevance(): bool
    {
        return $this->value > 0.8;
    }

    /**
     * Check if this is a medium relevance score.
     *
     * @return bool True if score is between 0.5 and 0.8
     */
    public function isMediumRelevance(): bool
    {
        return $this->value >= 0.5 && $this->value <= 0.8;
    }

    /**
     * Check if this is a low relevance score.
     *
     * @return bool True if score is below 0.5
     */
    public function isLowRelevance(): bool
    {
        return $this->value < 0.5;
    }

    /**
     * Get human-readable relevance description.
     *
     * @return string Description of relevance level
     */
    public function getDescription(): string
    {
        return match (true) {
            $this->isHighRelevance() => 'Высокая релевантность',
            $this->isMediumRelevance() => 'Средняя релевантность',
            default => 'Низкая релевантность',
        };
    }

    /**
     * Compare with another relevance score.
     *
     * @param RelevanceScore $other Other score
     *
     * @return int -1 if less, 0 if equal, 1 if greater
     */
    public function compareTo(RelevanceScore $other): int
    {
        return $this->value <=> $other->value;
    }

    /**
     * String representation as percentage.
     *
     * @return string Formatted percentage
     */
    public function __toString(): string
    {
        return $this->toPercentage().'%';
    }

    /**
     * Validate score value.
     *
     * @throws \InvalidArgumentException If score is out of valid range
     */
    private function validate(): void
    {
        if ($this->value < 0.0 || $this->value > 1.0) {
            throw new \InvalidArgumentException('Relevance score must be between 0.0 and 1.0, got: '.$this->value);
        }

        if (!is_finite($this->value)) {
            throw new \InvalidArgumentException('Relevance score must be a finite number');
        }
    }
}
