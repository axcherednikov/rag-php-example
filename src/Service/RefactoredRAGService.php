<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\ContextServiceInterface;
use App\Contract\DocumentRetrieverInterface;
use App\Contract\QueryProcessorInterface;
use App\Contract\ResponseGeneratorInterface;
use App\DTO\RAGSearchResult;

/**
 * Refactored RAG service following SOLID principles.
 *
 * Implements proper separation of concerns using dependency injection
 * and interfaces for each stage of the RAG pipeline:
 * 1. Query Processing - handled by QueryProcessorInterface
 * 2. Document Retrieval - handled by DocumentRetrieverInterface
 * 3. Response Generation - handled by ResponseGeneratorInterface
 *
 * This design follows:
 * - Single Responsibility: Each service handles one concern
 * - Open/Closed: Can extend functionality without modifying existing code
 * - Liskov Substitution: Any implementation can be substituted
 * - Interface Segregation: Interfaces are focused and cohesive
 * - Dependency Inversion: Depends on abstractions, not concretions
 */
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

    /**
     * Execute complete RAG pipeline with session context.
     *
     * @param string $userQuery Original user query
     * @param string $sessionId Unique session identifier
     *
     * @return RAGSearchResult Complete search result with all pipeline stages
     */
    public function searchWithContext(string $userQuery, string $sessionId): RAGSearchResult
    {
        // Get previous search context for this session
        $context = $this->contextService->getSearchContext($sessionId);

        // Stage 1: Query Processing
        $optimizedQuery = $this->queryProcessor->processQuery($userQuery, $context);

        // Determine category filter from context or query analysis
        $categoryFilter = $context ?? $this->contextService->inferCategoryFromQuery($userQuery);

        // Stage 2: Document Retrieval with optional category filtering
        $documents = $this->documentRetriever->retrieveDocuments(
            $optimizedQuery,
            $categoryFilter,
            self::DEFAULT_LIMIT,
            self::DEFAULT_THRESHOLD
        );

        // Retry without filter if no results found with category filter
        if (empty($documents) && null !== $categoryFilter) {
            $documents = $this->documentRetriever->retrieveDocuments(
                $optimizedQuery,
                null,
                self::DEFAULT_LIMIT,
                self::DEFAULT_THRESHOLD
            );
        }

        // Update session context based on results
        $this->updateSessionContext($sessionId, $documents, $userQuery);

        // Stage 3: Response Generation
        $aiResponse = $this->responseGenerator->generateResponse($documents, $userQuery);

        return new RAGSearchResult(
            originalQuery: $userQuery,
            optimizedQuery: $optimizedQuery,
            documents: $documents,
            aiResponse: $aiResponse
        );
    }

    public function healthCheck(): array
    {
        $health = [
            'query_processor' => $this->isQueryProcessorAvailable(),
            'document_retriever' => $this->isDocumentRetrieverAvailable(),
            'response_generator' => $this->isResponseGeneratorAvailable(),
            'context_service' => true, // Context service is always available (in-memory)
        ];

        // System is healthy if retrieval works (minimum requirement)
        // Query processing and response generation can use fallbacks
        $health['overall'] = $health['document_retriever'];

        return $health;
    }

    /**
     * Get comprehensive system statistics.
     *
     * @return array System statistics including collection info and health status
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
     * Update session context based on successful search results.
     *
     * @param string $sessionId Session identifier
     * @param array  $documents Retrieved documents
     * @param string $userQuery Original query
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

    /**
     * Check if query processor is available.
     *
     * @return bool True if query processor can be used
     */
    private function isQueryProcessorAvailable(): bool
    {
        try {
            // Test with a simple query
            $this->queryProcessor->processQuery('test query');

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Check if document retriever is available.
     *
     * @return bool True if document retriever can be used
     */
    private function isDocumentRetrieverAvailable(): bool
    {
        try {
            $stats = $this->documentRetriever->getCollectionStats();

            return !isset($stats['error']);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Check if response generator is available.
     *
     * @return bool True if response generator can be used
     */
    private function isResponseGeneratorAvailable(): bool
    {
        try {
            // Test with empty documents (should return fallback)
            $this->responseGenerator->generateResponse([], 'test query');

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
