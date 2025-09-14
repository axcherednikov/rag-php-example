<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * DTO for RAG search results.
 *
 * Provides a clean representation of RAG pipeline results
 * with original query, optimized query, documents, and AI response.
 */
final readonly class RAGSearchResult
{
    /**
     * @param array<int, array<string, mixed>> $documents
     */
    public function __construct(
        public string $originalQuery,
        public string $optimizedQuery,
        public array $documents,
        public string $aiResponse,
    ) {
    }

    /**
     * Check if search returned any results.
     */
    public function hasResults(): bool
    {
        return [] !== $this->documents;
    }

    public function getResultCount(): int
    {
        return count($this->documents);
    }

    public function getTopRelevanceScore(): ?float
    {
        if ([] === $this->documents) {
            return null;
        }

        return $this->documents[0]['score'] ?? null;
    }

    /**
     * Convert to array for API responses.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'original_query' => $this->originalQuery,
            'optimized_query' => $this->optimizedQuery,
            'documents' => $this->documents,
            'ai_response' => $this->aiResponse,
            'statistics' => [
                'result_count' => $this->getResultCount(),
                'top_relevance_score' => $this->getTopRelevanceScore(),
            ],
        ];
    }
}
