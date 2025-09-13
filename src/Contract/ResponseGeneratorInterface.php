<?php

declare(strict_types=1);

namespace App\Contract;

use App\Exception\RAGException;

/**
 * Interface for response generation stage of RAG pipeline.
 *
 * Represents the third stage of RAG where retrieved documents are used
 * to generate natural language responses to user queries.
 */
interface ResponseGeneratorInterface
{
    /**
     * Generate AI response based on retrieved documents.
     *
     * @param array<int, array<string, mixed>> $documents     Retrieved documents from vector database
     * @param string                           $originalQuery Original user query
     *
     * @return string Generated natural language response
     *
     * @throws RAGException If response generation fails
     */
    public function generateResponse(array $documents, string $originalQuery): string;
}
