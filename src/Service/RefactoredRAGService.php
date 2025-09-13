<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\ContextServiceInterface;
use App\Contract\DocumentRetrieverInterface;
use App\Contract\QueryProcessorInterface;
use App\Contract\ResponseGeneratorInterface;
use App\DTO\RAGSearchResult;

final class RefactoredRAGService implements RAGServiceInterface
{
    private const DEFAULT_LIMIT = 5;
    private const DEFAULT_THRESHOLD = 0.3;

    public function __construct(
        private readonly QueryProcessorInterface $queryProcessor,
        private readonly DocumentRetrieverInterface $documentRetriever,
        private readonly ResponseGeneratorInterface $responseGenerator,
        private readonly ContextServiceInterface $contextService,
    ) {
    }

    public function search(string $userQuery): RAGSearchResult
    {
        return $this->searchWithContext($userQuery, 'default_session');
    }

    public function searchWithContext(string $userQuery, string $sessionId): RAGSearchResult
    {
        $context = $this->contextService->getSearchContext($sessionId);
        $optimizedQuery = $this->queryProcessor->processQuery($userQuery, $context);
        $categoryFilter = $context ?? $this->contextService->inferCategoryFromQuery($userQuery);

        $documents = $this->documentRetriever->retrieveDocuments(
            $optimizedQuery,
            $categoryFilter,
            self::DEFAULT_LIMIT,
            self::DEFAULT_THRESHOLD
        );

        if (empty($documents) && null !== $categoryFilter) {
            $documents = $this->documentRetriever->retrieveDocuments(
                $optimizedQuery,
                null,
                self::DEFAULT_LIMIT,
                self::DEFAULT_THRESHOLD
            );
        }

        $this->updateSessionContext($sessionId, $documents, $userQuery);
        $aiResponse = $this->responseGenerator->generateResponse($documents, $userQuery);

        return new RAGSearchResult(
            originalQuery: $userQuery,
            optimizedQuery: $optimizedQuery,
            documents: $documents,
            aiResponse: $aiResponse
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        $health = [
            'query_processor' => $this->isQueryProcessorAvailable(),
            'document_retriever' => $this->isDocumentRetrieverAvailable(),
            'response_generator' => $this->isResponseGeneratorAvailable(),
            'context_service' => true,
        ];

        $health['overall'] = $health['document_retriever'];

        return $health;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSystemStats(): array
    {
        $stats = [
            'health' => $this->healthCheck(),
            'collection' => $this->documentRetriever->getCollectionStats(),
            'active_sessions' => $this->contextService->getActiveSessionsCount(),
        ];

        return $stats;
    }

    /**
     * @param array<int, array<string, mixed>> $documents
     */
    private function updateSessionContext(string $sessionId, array $documents, string $userQuery): void
    {
        if (empty($documents)) {
            return;
        }

        $category = $this->contextService->extractCategoryFromResults($documents);
        if (null !== $category) {
            $this->contextService->setSearchContext($sessionId, $category, $userQuery);
        }
    }

    private function isQueryProcessorAvailable(): bool
    {
        try {
            $this->queryProcessor->processQuery('test query');

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function isDocumentRetrieverAvailable(): bool
    {
        try {
            $stats = $this->documentRetriever->getCollectionStats();

            return !isset($stats['error']);
        } catch (\Exception) {
            return false;
        }
    }

    private function isResponseGeneratorAvailable(): bool
    {
        try {
            $this->responseGenerator->generateResponse([], 'test query');

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
