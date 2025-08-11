<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Исключение для ошибок RAG системы.
 */
class RAGException extends \Exception
{
    public static function queryProcessingFailed(string $query, ?\Throwable $previous = null): self
    {
        return new self("Ошибка обработки запроса: '$query'", 1001, $previous);
    }

    public static function retrievalFailed(string $message, ?\Throwable $previous = null): self
    {
        return new self("Ошибка поиска в векторной базе: $message", 1002, $previous);
    }

    public static function generationFailed(string $message, ?\Throwable $previous = null): self
    {
        return new self("Ошибка генерации ответа: $message", 1003, $previous);
    }

    public static function serviceUnavailable(string $service, ?\Throwable $previous = null): self
    {
        return new self("Сервис '$service' недоступен", 1004, $previous);
    }
}
