<?php

declare(strict_types=1);

namespace App\DTO;

use App\ValueObject\SearchQuery;

/**
 * Improved DTO for RAG search results using value objects.
 *
 * Provides a more robust and type-safe representation of RAG pipeline results
 * with better encapsulation and validation.
 */
final readonly class ImprovedRAGSearchResult
{
    /**
     * @param SearchResult[] $results
     */
    public function __construct(
        public SearchQuery $originalQuery,
        public SearchQuery $optimizedQuery,
        public array $results,
        public string $aiResponse,
        public float $processingTimeMs = 0.0,
    ) {
        $this->validate();
    }

    /**
     * Check if search returned any results.
     *
     * @return bool True if results are available
     */
    public function hasResults(): bool
    {
        return !empty($this->results);
    }

    /**
     * Get number of results.
     *
     * @return int Result count
     */
    public function getResultCount(): int
    {
        return count($this->results);
    }

    /**
     * Get the highest relevance score.
     *
     * @return float|null Top relevance score or null if no results
     */
    public function getTopRelevanceScore(): ?float
    {
        if (empty($this->results)) {
            return null;
        }

        return $this->results[0]->relevanceScore->value;
    }

    /**
     * Get average relevance score.
     *
     * @return float|null Average score or null if no results
     */
    public function getAverageRelevanceScore(): ?float
    {
        if (empty($this->results)) {
            return null;
        }

        $sum = array_sum(array_map(
            fn (SearchResult $result) => $result->relevanceScore->value,
            $this->results
        ));

        return $sum / count($this->results);
    }

    /**
     * Get results with high relevance only.
     *
     * @return SearchResult[] High relevance results
     */
    public function getHighRelevanceResults(): array
    {
        return array_filter(
            $this->results,
            fn (SearchResult $result) => $result->relevanceScore->isHighRelevance()
        );
    }

    /**
     * Get unique categories from results.
     *
     * @return string[] Array of unique categories
     */
    public function getCategories(): array
    {
        $categories = array_map(
            fn (SearchResult $result) => $result->category,
            $this->results
        );

        return array_unique($categories);
    }

    /**
     * Get unique brands from results.
     *
     * @return string[] Array of unique brands
     */
    public function getBrands(): array
    {
        $brands = array_map(
            fn (SearchResult $result) => $result->brand,
            $this->results
        );

        return array_unique($brands);
    }

    /**
     * Get price range from results.
     *
     * @return array{min: int, max: int}|null Price range or null if no results
     */
    public function getPriceRange(): ?array
    {
        if (empty($this->results)) {
            return null;
        }

        $prices = array_map(
            fn (SearchResult $result) => $result->priceInCents,
            $this->results
        );

        return [
            'min' => min($prices),
            'max' => max($prices),
        ];
    }

    /**
     * Convert to array for API responses.
     *
     * @return array Complete array representation
     */
    public function toArray(): array
    {
        return [
            'original_query' => $this->originalQuery->toString(),
            'optimized_query' => $this->optimizedQuery->toString(),
            'results' => array_map(
                fn (SearchResult $result) => $result->toArray(),
                $this->results
            ),
            'ai_response' => $this->aiResponse,
            'processing_time_ms' => $this->processingTimeMs,
            'statistics' => [
                'result_count' => $this->getResultCount(),
                'top_relevance_score' => $this->getTopRelevanceScore(),
                'average_relevance_score' => $this->getAverageRelevanceScore(),
                'categories' => $this->getCategories(),
                'brands' => $this->getBrands(),
                'price_range' => $this->getPriceRange(),
            ],
        ];
    }

    /**
     * Validate the DTO data.
     *
     * @throws \InvalidArgumentException If data is invalid
     */
    private function validate(): void
    {
        // Validate that all results are SearchResult instances
        foreach ($this->results as $result) {
            if (!$result instanceof SearchResult) {
                throw new \InvalidArgumentException('All results must be SearchResult instances');
            }
        }

        // Validate processing time
        if ($this->processingTimeMs < 0.0) {
            throw new \InvalidArgumentException('Processing time cannot be negative');
        }

        // Validate AI response
        if (empty(trim($this->aiResponse))) {
            throw new \InvalidArgumentException('AI response cannot be empty');
        }
    }
}
