<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\RAGSearchResult;
use App\Exception\RAGException;
use Qdrant\Config;
use Qdrant\Http\Transport;
use Qdrant\Models\Filter\Condition\MatchString;
use Qdrant\Models\Filter\Filter;
use Qdrant\Models\Request\SearchRequest;
use Qdrant\Models\VectorStruct;
use Qdrant\Qdrant;
use Symfony\Component\HttpClient\Psr18Client;

class RAGService implements RAGServiceInterface
{
    private const string COLLECTION_NAME = 'products';

    private const int DEFAULT_LIMIT = 5;

    private const float DEFAULT_THRESHOLD = 0.3;

    private ?Qdrant $qdrantClient = null;

    private ?EmbeddingConfigService $embeddingService = null;

    public function __construct(
        private readonly LlamaService $llamaService,
        private readonly ChatContextService $contextService,
    ) {
    }

    public function search(string $userQuery): RAGSearchResult
    {
        return $this->searchWithContext($userQuery, 'default_session');
    }

    public function searchWithContext(string $userQuery, string $sessionId): RAGSearchResult
    {
        $this->ensureInitialized();

        $context = $this->contextService->getSearchContext($sessionId);
        $optimizedQuery = $this->processQuery($userQuery, $context);
        $categoryFilter = null !== $context && '' !== $context && '0' !== $context ? $context : $this->contextService->inferCategoryFromQuery($userQuery);
        $documents = $this->retrieveDocuments($optimizedQuery, $categoryFilter);

        if ([] === $documents && $categoryFilter) {
            $documents = $this->retrieveDocuments($optimizedQuery, null);
        }

        if ([] !== $documents) {
            $category = $this->contextService->extractCategoryFromResults($documents);
            if (null !== $category && '' !== $category && '0' !== $category) {
                $this->contextService->setSearchContext($sessionId, $category, $userQuery);
            }
        }
        $aiResponse = $this->generateResponse($documents, $userQuery);

        return new RAGSearchResult(
            originalQuery: $userQuery,
            optimizedQuery: $optimizedQuery,
            documents: $documents,
            aiResponse: $aiResponse
        );
    }

    private function processQuery(string $userQuery, ?string $context = null): string
    {
        try {
            return $this->llamaService->analyzeSearchQuery($userQuery, $context);
        } catch (\Exception) {
            return $userQuery;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function retrieveDocuments(string $optimizedQuery, ?string $categoryFilter = null): array
    {
        if (null === $this->embeddingService) {
            throw RAGException::serviceUnavailable('Embedder not initialized');
        }

        $queryVector = $this->embeddingService->createEmbedding($optimizedQuery);

        $searchVector = new VectorStruct($queryVector, 'default');
        $searchRequest = new SearchRequest($searchVector);
        $searchRequest
            ->setLimit(self::DEFAULT_LIMIT)
            ->setWithPayload(true)
            ->setScoreThreshold(self::DEFAULT_THRESHOLD);

        if (null !== $categoryFilter && '' !== $categoryFilter && '0' !== $categoryFilter) {
            $filter = new Filter();
            $condition = new MatchString('category', $categoryFilter);
            $filter->addMust($condition);
            $searchRequest->setFilter($filter);
        }

        try {
            $response = $this->qdrantClient
                ?->collections(self::COLLECTION_NAME)
                ->points()
                ->search($searchRequest);

            return $response['result'] ?? [];
        } catch (\Exception $e) {
            throw RAGException::retrievalFailed($e->getMessage(), $e);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $documents
     */
    private function generateResponse(array $documents, string $originalQuery): string
    {
        if ([] === $documents) {
            return 'К сожалению, не найдено товаров соответствующих вашему запросу. Попробуйте изменить формулировку.';
        }

        try {
            return $this->llamaService->generateConstrainedResponse($documents, $originalQuery);
        } catch (\Exception) {
            $count = count($documents);
            $topProduct = $documents[0]['payload']['name'] ?? 'товар';

            return "Найдено $count товар(ов). Рекомендуем: $topProduct";
        }
    }

    private function ensureInitialized(): void
    {
        if (null !== $this->qdrantClient && null !== $this->embeddingService) {
            return;
        }

        if (in_array(getenv('KMP_DUPLICATE_LIB_OK'), ['', '0'], true) || false === getenv('KMP_DUPLICATE_LIB_OK')) {
            putenv('KMP_DUPLICATE_LIB_OK=TRUE');
        }

        if (null === $this->qdrantClient) {
            $config = new Config('http://localhost', 6333);
            $transport = new Transport(new Psr18Client(), $config);
            $this->qdrantClient = new Qdrant($transport);
        }

        if (null === $this->embeddingService) {
            $this->embeddingService = new EmbeddingConfigService();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        $health = [
            'llama_service' => false,
            'qdrant' => false,
            'embedder' => false,
            'overall' => false,
        ];

        try {
            $health['llama_service'] = $this->llamaService->isAvailable();
        } catch (\Exception) {
            $health['llama_service'] = false;
        }

        try {
            $this->ensureInitialized();

            $this->qdrantClient?->collections(self::COLLECTION_NAME)->info();
            $health['qdrant'] = true;

            $health['embedder'] = null !== $this->embeddingService;
        } catch (\Exception) {
        }

        $health['overall'] = $health['qdrant'] && $health['embedder'];

        return $health;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCollectionStats(): array
    {
        try {
            $this->ensureInitialized();
            $info = $this->qdrantClient?->collections(self::COLLECTION_NAME)->info();

            return [
                'vectors_count' => $info['result']['vectors_count'] ?? 0,
                'indexed_vectors_count' => $info['result']['indexed_vectors_count'] ?? 0,
                'status' => $info['result']['status'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
