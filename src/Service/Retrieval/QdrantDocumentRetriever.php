<?php

declare(strict_types=1);

namespace App\Service\Retrieval;

use App\Contract\DocumentRetrieverInterface;
use App\Contract\EmbeddingServiceInterface;
use App\Exception\RAGException;
use Qdrant\Config;
use Qdrant\Http\Transport;
use Qdrant\Models\Filter\Condition\MatchString;
use Qdrant\Models\Filter\Filter;
use Qdrant\Models\Request\SearchRequest;
use Qdrant\Models\VectorStruct;
use Qdrant\Qdrant;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Document retriever using Qdrant vector database.
 *
 * Handles vector similarity search and document retrieval
 * from Qdrant vector database with optional filtering.
 */
final class QdrantDocumentRetriever implements DocumentRetrieverInterface
{
    private const string COLLECTION_NAME = 'products';

    private const string DEFAULT_HOST = 'http://localhost';

    private const int DEFAULT_PORT = 6333;

    private ?Qdrant $qdrantClient = null;

    public function __construct(
        private readonly EmbeddingServiceInterface $embeddingService,
        private readonly string $host = self::DEFAULT_HOST,
        private readonly int $port = self::DEFAULT_PORT,
    ) {
    }

    public function retrieveDocuments(
        string $optimizedQuery,
        ?string $categoryFilter = null,
        int $limit = 5,
        float $threshold = 0.3,
    ): array {
        if (in_array(trim($optimizedQuery), ['', '0'], true)) {
            throw RAGException::retrievalFailed('Search query cannot be empty');
        }

        $this->ensureInitialized();

        try {
            // Generate query embedding
            $queryVector = $this->embeddingService->embed($optimizedQuery);

            // Create search request
            $searchVector = new VectorStruct($queryVector, 'default');
            $searchRequest = new SearchRequest($searchVector);
            $searchRequest
                ->setLimit($limit)
                ->setWithPayload(true)
                ->setScoreThreshold($threshold);

            // Add category filter if specified
            if (null !== $categoryFilter && '' !== $categoryFilter && '0' !== $categoryFilter) {
                $filter = new Filter();
                $condition = new MatchString('category', $categoryFilter);
                $filter->addMust($condition);
                $searchRequest->setFilter($filter);
            }

            // Execute search
            $response = $this->qdrantClient?->collections(self::COLLECTION_NAME)
                ->points()
                ->search($searchRequest);

            return $response['result'] ?? [];
        } catch (\Exception $e) {
            throw RAGException::retrievalFailed('Vector search failed: '.$e->getMessage(), $e);
        }
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
                'collection_name' => self::COLLECTION_NAME,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'collection_name' => self::COLLECTION_NAME,
            ];
        }
    }

    /**
     * Check if Qdrant service is available.
     *
     * @return bool True if Qdrant is accessible
     */
    public function isAvailable(): bool
    {
        try {
            $this->ensureInitialized();

            $this->qdrantClient?->collections(self::COLLECTION_NAME)->info();

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Initialize Qdrant client if not already done.
     *
     * @throws RAGException If initialization fails
     */
    private function ensureInitialized(): void
    {
        if (null !== $this->qdrantClient) {
            return;
        }

        try {
            $config = new Config($this->host, $this->port);
            $transport = new Transport(new Psr18Client(), $config);
            $this->qdrantClient = new Qdrant($transport);
        } catch (\Exception $e) {
            throw RAGException::serviceUnavailable('Failed to initialize Qdrant client: '.$e->getMessage(), $e);
        }
    }
}
