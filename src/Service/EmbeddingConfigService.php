<?php

declare(strict_types=1);

namespace App\Service;

use Codewithkyrian\Transformers\Exceptions\UnsupportedTaskException;
use Codewithkyrian\Transformers\Pipelines\Pipeline;

use function Codewithkyrian\Transformers\Pipelines\pipeline;

use Codewithkyrian\Transformers\Pipelines\Task;
use Codewithkyrian\Transformers\Tensor\Tensor;

final class EmbeddingConfigService
{
    private const string EMBEDDING_MODEL = 'onnx-community/Qwen3-Embedding-0.6B-ONNX';
    private ?int $cachedVectorSize = null;
    private ?Pipeline $embedder = null;

    public function getEmbeddingModel(): string
    {
        return self::EMBEDDING_MODEL;
    }

    public function getVectorSize(): int
    {
        if (null === $this->cachedVectorSize) {
            $this->cachedVectorSize = $this->detectVectorSize();
        }

        return $this->cachedVectorSize;
    }

    public function getEmbedder(): Pipeline
    {
        if (null === $this->embedder) {
            try {
                $this->embedder = pipeline(Task::Embeddings, self::EMBEDDING_MODEL);
            } catch (UnsupportedTaskException $e) {
                throw new \RuntimeException(sprintf('Embedding model "%s" is not supported or failed to load: %s', self::EMBEDDING_MODEL, $e->getMessage()), 0, $e);
            }
        }

        return $this->embedder;
    }

    /**
     * @return array<float>
     */
    public function createEmbedding(string $text): array
    {
        $embedding = ($this->getEmbedder())($text, pooling: 'mean', normalize: true);

        if (is_array($embedding)) {
            return $embedding[0];
        }

        return $embedding instanceof Tensor ? $embedding[0] : [];
    }

    private function detectVectorSize(): int
    {
        $testEmbedding = $this->createEmbedding('test');

        return count($testEmbedding);
    }
}
