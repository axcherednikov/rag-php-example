<?php

declare(strict_types=1);

namespace App\Service\Embedding;

use App\Contract\EmbeddingServiceInterface;
use App\Exception\RAGException;
use App\Util\EnvironmentSetup;
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
    private const DEFAULT_MODEL = 'onnx-community/Qwen3-Embedding-0.6B-ONNX';
    private const VECTOR_DIMENSIONS = 1024;

    private ?Pipeline $embedder = null;

    public function __construct(
        private readonly string $modelName = self::DEFAULT_MODEL,
    ) {
    }

    public function embed(string $text): array
    {
        $this->ensureInitialized();

        try {
            $result = ($this->embedder)($text, pooling: 'mean', normalize: true);

            if (!is_array($result) || empty($result)) {
                throw RAGException::serviceUnavailable('Embedding generation returned empty result');
            }

            return $result[0];
        } catch (\Exception $e) {
            throw RAGException::serviceUnavailable('Embedding service failed: '.$e->getMessage(), $e);
        }
    }

    public function getDimensions(): int
    {
        return self::VECTOR_DIMENSIONS;
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
        } catch (\Exception $e) {
            throw RAGException::serviceUnavailable('Failed to initialize embedding model: '.$e->getMessage(), $e);
        }
    }
}
