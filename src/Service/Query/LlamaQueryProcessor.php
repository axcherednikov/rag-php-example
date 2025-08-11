<?php

declare(strict_types=1);

namespace App\Service\Query;

use App\Contract\QueryProcessorInterface;
use App\Exception\RAGException;
use App\Service\LlamaService;

/**
 * Query processor using Llama LLM for query analysis and optimization.
 *
 * Handles natural language understanding and query optimization
 * using local Llama models through Ollama API.
 */
final class LlamaQueryProcessor implements QueryProcessorInterface
{
    public function __construct(
        private readonly LlamaService $llamaService,
    ) {
    }

    public function processQuery(string $userQuery, ?string $context = null): string
    {
        if (empty(trim($userQuery))) {
            throw RAGException::queryProcessingFailed('Query cannot be empty');
        }

        try {
            $optimizedQuery = $this->llamaService->analyzeSearchQuery($userQuery, $context);

            // Validate the result
            if (empty(trim($optimizedQuery))) {
                throw RAGException::queryProcessingFailed('LLM returned empty optimization result');
            }

            return $optimizedQuery;
        } catch (\Exception $e) {
            // Log the error but provide fallback
            error_log('Query processing failed: '.$e->getMessage());

            // Return the original query as fallback
            return $userQuery;
        }
    }
}
