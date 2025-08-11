<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * DTO для результата RAG поиска.
 */
readonly class RAGSearchResult
{
    public function __construct(
        public string $originalQuery,
        public string $optimizedQuery,
        public array $documents,
        public string $aiResponse,
    ) {
    }

    public function hasResults(): bool
    {
        return !empty($this->documents);
    }

    public function getDocumentCount(): int
    {
        return count($this->documents);
    }

    public function getTopScore(): ?float
    {
        if (empty($this->documents)) {
            return null;
        }

        return $this->documents[0]['score'] ?? null;
    }

    public function getAllScores(): array
    {
        return array_map(
            fn ($doc) => round(($doc['score'] ?? 0) * 100, 1),
            $this->documents
        );
    }

    public function getProducts(): array
    {
        return array_map(
            fn ($doc) => $doc['payload'] ?? [],
            $this->documents
        );
    }
}
