<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\RAGSearchResult;
use App\Exception\RAGException;
use Codewithkyrian\Transformers\Pipelines\Pipeline;

use function Codewithkyrian\Transformers\Pipelines\pipeline;

use Codewithkyrian\Transformers\Pipelines\Task;
use Qdrant\Config;
use Qdrant\Http\Transport;
use Qdrant\Models\Filter\Condition\MatchString;
use Qdrant\Models\Filter\Filter;
use Qdrant\Models\Request\SearchRequest;
use Qdrant\Models\VectorStruct;
use Qdrant\Qdrant;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Правильная реализация RAG-сервиса с тремя четкими этапами:
 * 1. Query Processing - обработка и оптимизация запроса
 * 2. Retrieval - поиск релевантных документов
 * 3. Generation - генерация ответа на основе найденных документов
 */
class ImprovedRAGService implements RAGServiceInterface
{
    private const COLLECTION_NAME = 'products';
    private const DEFAULT_LIMIT = 5;
    private const DEFAULT_THRESHOLD = 0.3;

    private ?Qdrant $qdrantClient = null;
    private ?Pipeline $embedder = null;

    public function __construct(
        private readonly LlamaService $llamaService,
        private readonly ChatContextService $contextService,
    ) {
    }

    /**
     * Главный метод RAG pipeline.
     */
    public function search(string $userQuery): RAGSearchResult
    {
        return $this->searchWithContext($userQuery, 'default_session');
    }

    /**
     * RAG поиск с учетом контекста сессии.
     */
    public function searchWithContext(string $userQuery, string $sessionId): RAGSearchResult
    {
        $this->ensureInitialized();

        // Получаем контекст предыдущих запросов
        $context = $this->contextService->getSearchContext($sessionId);

        // Этап 1: Query Processing с контекстом
        $optimizedQuery = $this->processQuery($userQuery, $context);

        // Попытаемся определить категорию для фильтрации
        $categoryFilter = $context ?: $this->contextService->inferCategoryFromQuery($userQuery);

        // Этап 2: Retrieval с возможной фильтрацией
        $documents = $this->retrieveDocuments($optimizedQuery, $categoryFilter);

        // Если с фильтром ничего не найдено, попробуем без фильтра
        if (empty($documents) && $categoryFilter) {
            $documents = $this->retrieveDocuments($optimizedQuery, null);
        }

        // Сохраняем контекст для следующих запросов
        if (!empty($documents)) {
            $category = $this->contextService->extractCategoryFromResults($documents);
            if ($category) {
                $this->contextService->setSearchContext($sessionId, $category, $userQuery);
            }
        }

        // Этап 3: Generation
        $aiResponse = $this->generateResponse($documents, $userQuery);

        return new RAGSearchResult(
            originalQuery: $userQuery,
            optimizedQuery: $optimizedQuery,
            documents: $documents,
            aiResponse: $aiResponse
        );
    }

    /**
     * ЭТАП 1: Query Processing
     * Анализирует запрос через LLM с учетом контекста и создает оптимизированный поисковый термин.
     */
    private function processQuery(string $userQuery, ?string $context = null): string
    {
        try {
            return $this->llamaService->analyzeSearchQuery($userQuery, $context);
        } catch (\Exception $e) {
            // Fallback: если LLM недоступен, используем исходный запрос
            return $userQuery;
        }
    }

    /**
     * ЭТАП 2: Retrieval
     * Выполняет векторный поиск в Qdrant с возможной фильтрацией по категории.
     *
     * @return array<int, array<string, mixed>>
     */
    private function retrieveDocuments(string $optimizedQuery, ?string $categoryFilter = null): array
    {
        // Векторизуем оптимизированный запрос
        if (null === $this->embedder) {
            throw RAGException::serviceUnavailable('Embedder not initialized');
        }

        $embedding = ($this->embedder)($optimizedQuery, pooling: 'mean', normalize: true);
        $queryVector = is_array($embedding) ? $embedding[0] : ($embedding instanceof \Codewithkyrian\Transformers\Tensor\Tensor ? $embedding[0] : []);

        // Создаем поисковый запрос для Qdrant
        $searchVector = new VectorStruct($queryVector, 'default');
        $searchRequest = new SearchRequest($searchVector);
        $searchRequest
            ->setLimit(self::DEFAULT_LIMIT)
            ->setWithPayload(true)
            ->setScoreThreshold(self::DEFAULT_THRESHOLD);

        // Добавляем фильтр по категории, если указана
        if ($categoryFilter) {
            $filter = new Filter();
            $condition = new MatchString('category', $categoryFilter);
            $filter->addMust($condition);
            $searchRequest->setFilter($filter);
        }

        // Выполняем поиск
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
     * ЭТАП 3: Generation
     * Генерирует персонализированный ответ на основе найденных документов.
     *
     * @param array<int, array<string, mixed>> $documents
     */
    private function generateResponse(array $documents, string $originalQuery): string
    {
        if (empty($documents)) {
            return 'К сожалению, не найдено товаров соответствующих вашему запросу. Попробуйте изменить формулировку.';
        }

        try {
            // Используем constrained generation для точного ответа
            return $this->llamaService->generateConstrainedResponse($documents, $originalQuery);
        } catch (\Exception $e) {
            // Fallback: базовый ответ без LLM
            $count = count($documents);
            $topProduct = $documents[0]['payload']['name'] ?? 'товар';

            return "Найдено $count товар(ов). Рекомендуем: $topProduct";
        }
    }

    /**
     * Инициализация всех необходимых сервисов.
     */
    private function ensureInitialized(): void
    {
        if (null !== $this->qdrantClient && null !== $this->embedder) {
            return;
        }

        // Настройка окружения для избежания конфликтов OpenMP
        if (!getenv('KMP_DUPLICATE_LIB_OK')) {
            putenv('KMP_DUPLICATE_LIB_OK=TRUE');
        }

        // Инициализация Qdrant клиента
        if (null === $this->qdrantClient) {
            $config = new Config('http://localhost', 6333);
            $transport = new Transport(new Psr18Client(), $config);
            $this->qdrantClient = new Qdrant($transport);
        }

        // Инициализация embedder модели
        if (null === $this->embedder) {
            $this->embedder = pipeline(Task::Embeddings, 'onnx-community/Qwen3-Embedding-0.6B-ONNX');
        }
    }

    /**
     * Проверка готовности всех компонентов RAG системы.
     *
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
            // Проверка LlamaService
            $health['llama_service'] = $this->llamaService->isAvailable();
        } catch (\Exception) {
            $health['llama_service'] = false;
        }

        try {
            // Инициализируем если нужно
            $this->ensureInitialized();

            // Проверка Qdrant
            $this->qdrantClient?->collections(self::COLLECTION_NAME)->info();
            $health['qdrant'] = true;

            // Проверка embedder (если инициализирован без ошибок, то работает)
            $health['embedder'] = null !== $this->embedder;
        } catch (\Exception) {
            // Qdrant или embedder недоступны
        }

        // Общая готовность - можем работать даже без LLM, но нужны Qdrant + embedder
        $health['overall'] = $health['qdrant'] && $health['embedder'];

        return $health;
    }

    /**
     * Получить статистику коллекции.
     *
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
