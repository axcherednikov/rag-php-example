<?php

declare(strict_types=1);

namespace App\Service\Generation;

use App\Contract\ResponseGeneratorInterface;
use App\Exception\RAGException;
use App\Service\LlamaService;

/**
 * Response generator using Llama LLM for natural language generation.
 *
 * Creates contextual responses based on retrieved documents
 * using constrained generation to prevent hallucination.
 */
final class LlamaResponseGenerator implements ResponseGeneratorInterface
{
    private const DEFAULT_EMPTY_RESPONSE = 'К сожалению, не найдено товаров соответствующих вашему запросу. Попробуйте изменить формулировку.';

    public function __construct(
        private readonly LlamaService $llamaService,
    ) {
    }

    public function generateResponse(array $documents, string $originalQuery): string
    {
        if (empty($documents)) {
            return self::DEFAULT_EMPTY_RESPONSE;
        }

        if (empty(trim($originalQuery))) {
            throw RAGException::generationFailed('Original query cannot be empty');
        }

        try {
            $response = $this->llamaService->generateConstrainedResponse($documents, $originalQuery);

            // Validate generated response
            if (empty(trim($response))) {
                return $this->generateFallbackResponse($documents);
            }

            return $response;
        } catch (\Exception $e) {
            // Log the error but provide fallback
            error_log('Response generation failed: '.$e->getMessage());

            return $this->generateFallbackResponse($documents);
        }
    }

    /**
     * Generate a simple fallback response when LLM fails.
     *
     * @param array $documents Retrieved documents
     *
     * @return string Basic fallback response
     */
    private function generateFallbackResponse(array $documents): string
    {
        if (empty($documents)) {
            return self::DEFAULT_EMPTY_RESPONSE;
        }

        $count = count($documents);
        $topProduct = $documents[0]['payload']['name'] ?? 'товар';

        return "Найдено {$count} товар(ов). Рекомендуем: {$topProduct}";
    }
}
