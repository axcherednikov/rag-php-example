<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\RAGSearchResult;

/**
 * Интерфейс для RAG сервиса.
 */
interface RAGServiceInterface
{
    /**
     * Выполняет поиск по RAG pipeline.
     */
    public function search(string $userQuery): RAGSearchResult;

    /**
     * Проверяет готовность всех компонентов системы.
     *
     * @return array<string, mixed>
     */
    public function healthCheck(): array;
}
