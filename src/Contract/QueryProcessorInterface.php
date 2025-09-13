<?php

declare(strict_types=1);

namespace App\Contract;

use App\Exception\RAGException;

/**
 * Interface for query processing stage of RAG pipeline.
 *
 * Represents the first stage of RAG where user queries are analyzed,
 * optimized and prepared for vector search.
 */
interface QueryProcessorInterface
{
    /**
     * Process and optimize user query for better search results.
     *
     * @param string      $userQuery Original user query in any language
     * @param string|null $context   Previous search context if available
     *
     * @return string Optimized search query for vector database
     *
     * @throws RAGException If query processing fails
     */
    public function processQuery(string $userQuery, ?string $context = null): string;
}
