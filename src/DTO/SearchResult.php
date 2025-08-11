<?php

declare(strict_types=1);

namespace App\DTO;

use App\ValueObject\RelevanceScore;

/**
 * DTO representing a single search result document.
 *
 * Encapsulates document data with proper typing and validation
 * following Domain-Driven Design principles.
 */
final readonly class SearchResult
{
    public function __construct(
        public string $id,
        public string $name,
        public string $brand,
        public string $category,
        public string $description,
        public int $priceInCents,
        public RelevanceScore $relevanceScore,
    ) {
    }

    /**
     * Create from Qdrant search result array.
     *
     * @param array $qdrantResult Raw result from Qdrant
     *
     * @return self Typed search result
     */
    public static function fromQdrantResult(array $qdrantResult): self
    {
        $payload = $qdrantResult['payload'] ?? [];
        $score = $qdrantResult['score'] ?? 0.0;

        return new self(
            id: (string) ($qdrantResult['id'] ?? ''),
            name: (string) ($payload['name'] ?? 'Unknown Product'),
            brand: (string) ($payload['brand'] ?? 'Unknown Brand'),
            category: (string) ($payload['category'] ?? 'Unknown Category'),
            description: (string) ($payload['description'] ?? 'No description available'),
            priceInCents: (int) ($payload['price'] ?? 0),
            relevanceScore: RelevanceScore::fromFloat((float) $score),
        );
    }

    /**
     * Get formatted price in currency format.
     *
     * @return string Formatted price string
     */
    public function getFormattedPrice(): string
    {
        if ($this->priceInCents <= 0) {
            return 'Цена не указана';
        }

        return number_format($this->priceInCents / 100, 0, '.', ' ').' ₽';
    }

    /**
     * Get short description (first sentence or 100 characters).
     *
     * @return string Truncated description
     */
    public function getShortDescription(): string
    {
        // Try to get first sentence
        if (preg_match('/^[^.!?]*[.!?]/', $this->description, $matches)) {
            return trim($matches[0]);
        }

        // Fallback to character limit
        if (mb_strlen($this->description) > 100) {
            return mb_substr($this->description, 0, 97).'...';
        }

        return $this->description;
    }

    /**
     * Check if product is high-value (expensive).
     *
     * @return bool True if price is above 100,000 cents (1000 currency units)
     */
    public function isHighValue(): bool
    {
        return $this->priceInCents > 100_000;
    }

    /**
     * Get relevance percentage for display.
     *
     * @return float Relevance as percentage
     */
    public function getRelevancePercentage(): float
    {
        return $this->relevanceScore->toPercentage();
    }

    /**
     * Convert to array for API responses.
     *
     * @return array Associative array representation
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand,
            'category' => $this->category,
            'description' => $this->description,
            'price_in_cents' => $this->priceInCents,
            'formatted_price' => $this->getFormattedPrice(),
            'relevance_score' => $this->relevanceScore->value,
            'relevance_percentage' => $this->getRelevancePercentage(),
            'relevance_description' => $this->relevanceScore->getDescription(),
        ];
    }
}
