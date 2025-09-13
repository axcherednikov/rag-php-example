<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\ContextServiceInterface;

/**
 * Service for storing chat session context.
 *
 * Helps the system remember conversation history and context
 * to improve search accuracy in multi-turn conversations.
 */
final class ChatContextService implements ContextServiceInterface
{
    /** @var array<string, array<string, mixed>> */
    private array $contexts = [];

    /**
     * Сохранить контекст поиска для сессии.
     */
    public function setSearchContext(string $sessionId, string $category, string $query): void
    {
        $this->contexts[$sessionId] = [
            'category' => $category,
            'last_query' => $query,
            'timestamp' => time(),
        ];
    }

    /**
     * Получить контекст для анализа следующего запроса.
     */
    public function getSearchContext(string $sessionId): ?string
    {
        if (!isset($this->contexts[$sessionId])) {
            return null;
        }

        $context = $this->contexts[$sessionId];

        // Контекст устаревает через 10 минут
        if (time() - $context['timestamp'] > 600) {
            unset($this->contexts[$sessionId]);

            return null;
        }

        return $context['category'];
    }

    /**
     * Определить категорию товара по результатам поиска.
     *
     * @param array<int, array<string, mixed>> $searchResults
     */
    public function extractCategoryFromResults(array $searchResults): ?string
    {
        if ([] === $searchResults) {
            return null;
        }

        // Берем категорию самого релевантного товара
        return $searchResults[0]['payload']['category'] ?? null;
    }

    /**
     * Попытаться определить категорию из запроса.
     */
    public function inferCategoryFromQuery(string $query): ?string
    {
        $query = mb_strtolower($query);

        // Простые правила для определения категории
        $categoryMap = [
            'graphics_cards' => ['видеокарт', 'видео карт', 'rtx', 'gtx', 'radeon', 'rx ', 'gpu'],
            'processors' => ['процессор', 'процесс', 'cpu', 'ryzen', 'intel', 'core', 'amd'],
            'laptops' => ['ноутбук', 'laptop', 'macbook', 'notebook'],
            'motherboards' => ['материнск', 'материнка', 'мать', 'motherboard'],
            'memory' => ['память', 'ram', 'ddr', 'оперативка'],
            'storage' => ['ssd', 'hdd', 'диск', 'накопитель'],
            'cooling' => ['охлаждение', 'кулер', 'cooler', 'вентилятор'],
        ];

        foreach ($categoryMap as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($query, $keyword)) {
                    return $category;
                }
            }
        }

        return null;
    }

    /**
     * Очистить старые контексты (для экономии памяти).
     */
    public function cleanupOldContexts(): void
    {
        $currentTime = time();
        foreach ($this->contexts as $sessionId => $context) {
            if ($currentTime - $context['timestamp'] > 3600) { // 1 час
                unset($this->contexts[$sessionId]);
            }
        }
    }

    /**
     * Получить статистику активных сессий.
     */
    public function getActiveSessionsCount(): int
    {
        $this->cleanupOldContexts();

        return count($this->contexts);
    }
}
