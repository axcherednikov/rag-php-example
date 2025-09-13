<?php

declare(strict_types=1);

namespace App\Contract;

use App\Exception\RAGException;

/**
 * Interface for document retrieval stage of RAG pipeline.
 *
 * Represents the second stage of RAG where optimized queries are used
 * to retrieve relevant documents from vector database.
 */
interface DocumentRetrieverInterface
{
    /**
     * Retrieve relevant documents from vector database.
     *
     * @param string      $optimizedQuery Processed query ready for vector search
     * @param string|null $categoryFilter Optional category filter
     * @param int         $limit          Maximum number of documents to retrieve
     * @param float       $threshold      Minimum relevance score threshold
     *
     * @return array<int, array<string, mixed>> Array of retrieved documents with scores and metadata
     *
     * @throws RAGException If retrieval fails
     */
    public function retrieveDocuments(
        string $optimizedQuery,
        ?string $categoryFilter = null,
        int $limit = 5,
        float $threshold = 0.3,
    ): array;

    /**
     * Get collection statistics.
     *
     * @return array<string, mixed> Collection statistics including vector count and status
     */
    public function getCollectionStats(): array;
}
