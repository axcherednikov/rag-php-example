<?php

declare(strict_types=1);

namespace App\Contract;

/**
 * Interface for text embedding generation.
 *
 * Provides methods to convert text into numerical vectors
 * for semantic similarity search in vector databases.
 */
interface EmbeddingServiceInterface
{
    /**
     * Generate embedding vector from text.
     *
     * @param string $text Input text to convert to vector
     *
     * @return array Numerical vector representation of the text
     *
     * @throws \App\Exception\RAGException If embedding generation fails
     */
    public function embed(string $text): array;

    /**
     * Get the dimension size of generated embeddings.
     *
     * @return int Vector dimension size
     */
    public function getDimensions(): int;

    /**
     * Get the model name used for embeddings.
     *
     * @return string Model identifier
     */
    public function getModelName(): string;

    /**
     * Check if embedding service is available.
     *
     * @return bool True if service is ready to use
     */
    public function isAvailable(): bool;
}
