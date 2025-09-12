<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class LlamaService
{
    private const OLLAMA_URL = 'http://localhost:11434';
    private const DEFAULT_MODEL = 'llama3.2:1b';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Анализирует русский поисковый запрос с учетом контекста и возвращает оптимизированный английский термин.
     */
    public function analyzeSearchQuery(string $russianQuery, ?string $previousContext = null): string
    {
        $prompt = $this->buildSearchAnalysisPrompt($russianQuery, $previousContext);

        // Используем более мощную модель для лучшего анализа запросов
        $response = $this->generateText($prompt, 'llama3.2:3b');

        // Извлекаем только чистый поисковый термин из ответа
        return $this->extractSearchTerm($response);
    }

    /**
     * Генерирует персонализированный ответ на основе результатов поиска.
     *
     * @param array<int, array<string, mixed>> $searchResults
     */
    public function generateProductResponse(array $searchResults, string $originalQuery): string
    {
        $prompt = $this->buildResponsePrompt($searchResults, $originalQuery);

        return $this->generateText($prompt);
    }

    /**
     * НОВЫЙ МЕТОД: Генерирует строго ограниченный ответ только на основе найденных товаров
     * LLM НЕ должна ничего добавлять от себя, работает только с предоставленным контекстом
     *
     * @param array<int, array<string, mixed>> $searchResults
     */
    public function generateConstrainedResponse(array $searchResults, string $originalQuery): string
    {
        if (empty($searchResults)) {
            return 'К сожалению, не найдено товаров соответствующих вашему запросу.';
        }

        $prompt = $this->buildConstrainedResponsePrompt($searchResults, $originalQuery);

        return $this->generateText($prompt);
    }

    /**
     * Базовый метод для генерации текста через Ollama API.
     */
    private function generateText(string $prompt, string $model = self::DEFAULT_MODEL): string
    {
        try {
            // Очищаем строку от некорректных UTF-8 символов
            $cleanPrompt = mb_convert_encoding($prompt, 'UTF-8', 'UTF-8');

            $response = $this->httpClient->request('POST', self::OLLAMA_URL.'/api/generate', [
                'json' => [
                    'model' => $model,
                    'prompt' => $cleanPrompt,
                    'stream' => false,
                    'options' => [
                        'temperature' => 0.1, // Низкая температура для более точных ответов
                        'top_p' => 0.9,
                        'num_predict' => 500, // Увеличиваем лимит для более полных ответов
                    ],
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray();

            return trim($data['response'] ?? '');
        } catch (\Exception $e) {
            throw new \RuntimeException('Ошибка при обращении к Llama: '.$e->getMessage());
        }
    }

    /**
     * Создает промпт для анализа поискового запроса с учетом контекста.
     */
    private function buildSearchAnalysisPrompt(string $query, ?string $context = null): string
    {
        $contextInfo = '';
        if ($context) {
            $contextInfo = "Previous context: User was looking for $context\n\n";
        }

        return <<<PROMPT
You are a smart search query analyzer for computer products. Your task:

1. Understand the Russian query in context
2. Identify the product category (graphics_cards, processors, laptops, etc.)
3. Return ONLY the optimized English search term

{$contextInfo}Examples:
процессор AMD для игр → AMD gaming processor
видеокарта RTX дешевая → RTX budget graphics card  
игровая видеокарта → gaming graphics card
MacBook для работы → MacBook laptop
материнская плата → motherboard
А как же AMD? → AMD graphics card (if previous context was graphics cards)
AMD Ryzen 9 → AMD Ryzen 9 processor

Current query: $query
Return only the English search term:
PROMPT;
    }

    /**
     * Создает промпт для генерации ответа пользователю.
     *
     * @param array<int, array<string, mixed>> $searchResults
     */
    private function buildResponsePrompt(array $searchResults, string $originalQuery): string
    {
        $resultsText = '';
        foreach ($searchResults as $i => $result) {
            $payload = $result['payload'];
            $score = $result['score'];
            $price = number_format($payload['price'] / 100, 0, '.', ' ');

            $resultsText .= ($i + 1).". {$payload['name']} - {$price} ₽ (релевантность: ".round($score, 2).")\n";
            $resultsText .= "   {$payload['description']}\n\n";
        }

        return <<<PROMPT
Ты консультант в магазине компьютерной техники. Пользователь искал: "$originalQuery"

Найденные товары:
$resultsText

Задача: Дай краткую рекомендацию (2-3 предложения) на русском языке:
- Какой товар лучше всего подходит
- Почему именно этот вариант
- Укажи цену

Ответ должен быть дружелюбным и профессиональным.
PROMPT;
    }

    /**
     * УЛУЧШЕННЫЙ ПРОМПТ: Строго ограниченный промпт для RAG с улучшенной логикой
     * LLM должна работать ТОЛЬКО с предоставленными товарами, ничего не добавлять от себя.
     *
     * @param array<int, array<string, mixed>> $searchResults
     */
    private function buildConstrainedResponsePrompt(array $searchResults, string $originalQuery): string
    {
        $resultsText = '';
        foreach ($searchResults as $i => $result) {
            $payload = $result['payload'];
            $score = $result['score'];
            $price = number_format($payload['price'] / 100, 0, '.', ' ');
            $relevance = round($score * 100, 1);

            $resultsText .= ($i + 1).". {$payload['name']}\n";
            $resultsText .= "   Бренд: {$payload['brand']}\n";
            $resultsText .= "   Категория: {$payload['category']}\n";
            $resultsText .= "   Цена: {$price} ₽\n";
            $resultsText .= "   Описание: {$payload['description']}\n";
            $resultsText .= "   Релевантность запросу: {$relevance}%\n\n";
        }

        return <<<PROMPT
Ты эксперт-консультант в магазине компьютерной техники. 

ЗАПРОС ПОЛЬЗОВАТЕЛЯ: "$originalQuery"

НАЙДЕННЫЕ ТОВАРЫ (только эти товары существуют в наличии):
$resultsText

СТРОГИЕ ПРАВИЛА:
1. Рекомендуй ТОЛЬКО товары из списка выше
2. НЕ упоминай товары, которых нет в списке
3. НЕ добавляй информацию о характеристиках, которых нет в описании
4. Выбери ОДИН лучший товар из списка

ЗАДАЧА: Дай краткую профессиональную рекомендацию (2-3 предложения):
- Какой товар из списка лучше всего подходит
- Почему именно этот вариант (на основе описания и категории)
- Укажи точную цену

Пиши на русском языке, будь дружелюбным и компетентным.
PROMPT;
    }

    /**
     * Извлекает чистый поисковый термин из ответа модели.
     */
    private function extractSearchTerm(string $response): string
    {
        // Убираем лишние символы и берем первую строку
        $lines = explode("\n", trim($response));
        $searchTerm = trim($lines[0]);

        // Убираем кавычки и лишние символы
        $searchTerm = trim($searchTerm, '"\'');

        // Убираем различные префиксы которые может добавить LLM
        $prefixes = [
            '/^(Search query:\s*|Answer:\s*|Result:\s*|English term:\s*)/i',
            '/^(Translation:\s*|English:\s*|Translate:\s*)/i',
            '/^(Here\s+(?:is|are)\s+the\s+translation(?:s)?:\s*)/i',
            '/^(The\s+English\s+translation\s+is:\s*)/i',
        ];

        foreach ($prefixes as $pattern) {
            $searchTerm = preg_replace($pattern, '', $searchTerm) ?? $searchTerm;
        }

        $searchTerm = trim($searchTerm, '"\'');

        // Если все еще содержит лишний текст, берем только первую часть до разделителей
        if (preg_match('/^([^.!?\n]+)/', $searchTerm, $matches)) {
            $searchTerm = trim($matches[1]);
        }

        // Если пустой ответ, возвращаем исходный
        return empty($searchTerm) ? $response : $searchTerm;
    }

    /**
     * Проверяет доступность Ollama сервера.
     */
    public function isAvailable(): bool
    {
        try {
            $response = $this->httpClient->request('GET', self::OLLAMA_URL.'/api/tags', [
                'timeout' => 5,
            ]);

            return 200 === $response->getStatusCode();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Получает список доступных моделей.
     *
     * @return array<int, string>
     */
    public function getAvailableModels(): array
    {
        try {
            $response = $this->httpClient->request('GET', self::OLLAMA_URL.'/api/tags');
            $data = $response->toArray();

            return array_map(fn ($model) => $model['name'] ?? '', $data['models'] ?? []);
        } catch (\Exception) {
            return [];
        }
    }
}
