<?php

declare(strict_types=1);

namespace App\Service\Embedding;

use App\Contract\EmbeddingServiceInterface;
use App\Exception\RAGException;
use App\Util\EnvironmentSetup;
use Codewithkyrian\Transformers\Exceptions\UnsupportedTaskException;
use Codewithkyrian\Transformers\Pipelines\Pipeline;

use function Codewithkyrian\Transformers\Pipelines\pipeline;

use Codewithkyrian\Transformers\Pipelines\Task;

/**
 * Embedding service using Transformers PHP library.
 *
 * Provides text-to-vector conversion using ONNX models
 * for semantic similarity search.
 */
final class TransformersEmbeddingService implements EmbeddingServiceInterface
{
    private const string DEFAULT_MODEL = 'onnx-community/Qwen3-Embedding-0.6B-ONNX';

    private ?int $cachedVectorSize = null;

    private ?Pipeline $embedder = null;

    public function __construct(
        private readonly string $modelName = self::DEFAULT_MODEL,
    ) {
    }

    /**
     * @return array<int, float>
     */
    public function embed(string $text): array
    {
        $this->ensureInitialized();

        try {
            if (null === $this->embedder) {
                throw RAGException::serviceUnavailable('Embedder not initialized');
            }

            $result = ($this->embedder)($text, pooling: 'mean', normalize: true);

            if (!is_array($result) || [] === $result) {
                throw RAGException::serviceUnavailable('Embedding generation returned empty result');
            }

            return $result[0];
        } catch (\Exception $e) {
            throw RAGException::serviceUnavailable('Embedding service failed: '.$e->getMessage(), $e);
        }
    }

    public function getDimensions(): int
    {
        if (null === $this->cachedVectorSize) {
            $this->cachedVectorSize = $this->detectVectorSize();
        }

        return $this->cachedVectorSize;
    }

    public function getModelName(): string
    {
        return $this->modelName;
    }

    public function isAvailable(): bool
    {
        try {
            $this->ensureInitialized();

            return null !== $this->embedder;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Initialize the embedding pipeline if not already done.
     *
     * @throws RAGException If initialization fails
     */
    private function ensureInitialized(): void
    {
        if (null !== $this->embedder) {
            return;
        }

        // Configure ML environment
        EnvironmentSetup::configureMLEnvironment();

        try {
            $this->embedder = pipeline(Task::Embeddings, $this->modelName);
        } catch (UnsupportedTaskException $e) {
            throw RAGException::serviceUnavailable(sprintf('Embedding model "%s" is not supported: %s', $this->modelName, $e->getMessage()), $e);
        }
    }

    private function detectVectorSize(): int
    {
        $testEmbedding = $this->embed('test');

        return count($testEmbedding);
    }
}
