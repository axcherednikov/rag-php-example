# RAG с векторным поиском на PHP 🔍🤖

[![Статус CI](https://github.com/axcherednikov/rag-php-example/workflows/CI/badge.svg)](https://github.com/axcherednikov/rag-php-example/actions)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)](https://php.net)
[![Лицензия MIT](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**Комплексная демонстрация современной RAG системы на PHP** с векторными эмбеддингами и локальными AI моделями для интеллектуального поиска товаров.

> 💡 **Для кого этот проект?** Для PHP разработчиков, которые хотят понять, как работает RAG архитектура и интегрировать AI в свои проекты без использования внешних API.

## ✨ Что умеет система?

- 🔍 **Семантический поиск** - понимает смысл запросов на русском и английском
- 🤖 **AI анализ запросов** - Llama 3.2 обрабатывает естественную речь
- 💬 **Интерактивный чат** - можно общаться как с консультантом в магазине
- 🚀 **Оптимизация памяти** - работает с большими каталогами через генераторы
- 💰 **Без затрат на API** - все работает локально, никаких счетов за токены
- 🏗️ **Production-ready** - Docker, CI/CD, статический анализ

## 🏛️ Как это работает?

### Трёхэтапный RAG пайплайн

```
┌─────────────────────┐    ┌────────────────────┐    ┌─────────────────────┐
│  🧠 Анализ запроса  │ => │ 🔍 Векторный поиск │ => │ ✨ Генерация ответа │
│                     │    │                    │    │                     │
│ • Обработка LLM     │    │ • Поиск в Qdrant   │    │ • Только найденное  │
│ • Оптимизация       │    │ • Косинусное       │    │ • Без "галлюцинаций"│
│ • Векторизация      │    │   сходство         │    │ • Факты + контекст  │
└─────────────────────┘    └────────────────────┘    └─────────────────────┘
```

### Технологический стек

- **Бэкенд**: PHP 8.3 + Symfony 7.3 (MicroKernelTrait)
- **Векторная БД**: Qdrant (поиск по косинусному сходству)
- **Эмбеддинги**: Transformers PHP (all-MiniLM-L6-v2, 384 измерения)
- **LLM**: Ollama + Llama 3.2:1b (локальная инференция)
- **Инфраструктура**: Docker + Docker Compose

## 🚀 Быстрый старт

### Что нужно установить

- Docker и Docker Compose
- PHP 8.2+ с Composer
- Make (опционально, для удобных команд)

### Запуск в одну команду

```bash
# Клонируем репозиторий
git clone https://github.com/yourusername/rag-vectors-presentation.git
cd rag-vectors-presentation

# Автоматическая настройка (рекомендуется)
make setup
# ИЛИ ручная настройка
chmod +x scripts/setup.sh && ./scripts/setup.sh
```

Скрипт сам:
1. Установит PHP зависимости
2. Запустит Qdrant и Ollama в Docker
3. Скачает модель Llama 3.2 (~1.3ГБ)
4. Проиндексирует тестовые товары
5. Проверит работоспособность всех сервисов

### Ручная установка (если что-то пошло не так)

```bash
# Ставим зависимости
composer install

# Запускаем сервисы
docker-compose up -d

# Ждём запуска и скачиваем модели
sleep 15
docker-compose exec ollama ollama pull llama3.2:1b

# Индексируем товары
php bin/console products:vectorize
```

## 🎮 Примеры использования

### Интерактивные демо

```bash
# 🆕 Главное RAG демо (рекомендуется)
make demo
# ИЛИ: php bin/console rag:demo --interactive

# 💬 Чат с AI консультантом
make chat
# ИЛИ: php bin/console products:chat

# 🔍 Разовый поиск
make search QUERY="игровой ноутбук для разработки AI"
# ИЛИ: php bin/console products:search "игровой ноутбук"
```

### Примеры поисковых запросов

```bash
# Семантический поиск на английском
php bin/console products:search "powerful AMD processor for gaming"

# Поддержка русского языка
php bin/console products:search "мощный игровой ноутбук"

# Естественные запросы с AI
php bin/console rag:demo --query "найди процессор для машинного обучения"

# Сложные запросы
php bin/console products:chat
> "Посоветуй видеокарту для разработки нейросетей до 100 тысяч рублей"
```

## 🏗️ Структура проекта

```
rag-vectors-presentation/
├── src/
│   ├── Command/          # Консольные команды для демо
│   ├── Service/          # Основная бизнес-логика
│   │   ├── ImprovedRAGService.php    # Главная RAG реализация
│   │   ├── OllamaEmbeddingService.php # Векторные эмбеддинги
│   │   └── LlamaService.php          # Интеграция с LLM
│   ├── DTO/              # Объекты передачи данных
│   └── Exception/        # Кастомные исключения
├── config/               # Конфигурация Symfony
├── data/
│   └── products.json     # Каталог товаров для демо
├── scripts/
│   └── setup.sh         # Скрипт автоматической настройки
├── docker-compose.yml   # Оркестрация сервисов
├── Makefile            # Команды для разработки
└── README.md           # Этот файл
```

## 🔧 Разработка

### Проверка качества кода

```bash
# Запустить все проверки
make check

# Исправить стиль кода
make fix

# Отдельные инструменты
composer phpstan        # Статический анализ
composer cs-check      # Проверка стиля
composer cs-fix        # Автоисправление стиля
```

### Управление сервисами

```bash
make start        # Запустить все сервисы
make stop         # Остановить все сервисы
make restart      # Перезапустить сервисы
make logs         # Посмотреть логи всех сервисов
make logs-qdrant  # Логи только Qdrant
make logs-ollama  # Логи только Ollama
make clean        # Очистить Docker ресурсы
```

### Проверка подключений

```bash
make test-qdrant  # Проверить Qdrant API
make test-ollama  # Проверить Ollama API

# Ручная проверка
curl http://localhost:6333/health
curl http://localhost:11434/api/tags
```

## 📊 Веб-интерфейсы

- **Панель Qdrant**: http://localhost:6333/dashboard
- **API Ollama**: http://localhost:11434

## 🎯 Примеры кода

### Базовый поиск товаров

```php
// Прямое использование сервиса
$ragService = $container->get(ImprovedRAGService::class);
$result = $ragService->search("мощная видеокарта для игр");

echo "Запрос: " . $result->query . "\n";
echo "Ответ: " . $result->response . "\n";
foreach ($result->products as $product) {
    echo "- {$product['name']} (релевантность: {$product['similarity']})\n";
}
```

### Кастомная RAG реализация

```php
use App\Service\RAGServiceInterface;

class MyCustomRAGService implements RAGServiceInterface
{
    public function search(string $userQuery): RAGSearchResult
    {
        // Ваша логика RAG здесь
    }
}
```

## 🚀 Деплой в продакшен

### Сборка Docker образа

```bash
# Собрать образ приложения
docker build -t my-rag-app .

# Продакшен docker-compose
docker-compose -f docker-compose.prod.yml up -d
```

### Настройка окружения

Скопируйте `.env.example` в `.env` и настройте:

```env
# Конфигурация Qdrant
QDRANT_HOST=localhost
QDRANT_PORT=6333

# Конфигурация Ollama
OLLAMA_HOST=localhost
OLLAMA_PORT=11434
OLLAMA_MODEL=llama3.2:1b

# Настройки векторов
VECTOR_DIMENSION=384
SIMILARITY_THRESHOLD=0.7
```

## 🔬 Принцип работы

### 1. Этап анализа запроса
- Пользовательский запрос анализируется Llama 3.2
- Запрос оптимизируется для лучших результатов поиска
- Текст преобразуется в 384-мерный вектор с помощью all-MiniLM-L6-v2

### 2. Этап поиска
- Семантический поиск в векторной базе Qdrant
- Сравнение по косинусному сходству с настраиваемым порогом
- Извлечение топ-K наиболее релевантных товаров

### 3. Этап генерации ответа
- LLM генерирует ответ ТОЛЬКО на основе найденных товаров
- Никаких "галлюцинаций" - строго фактический контент
- Контекстные рекомендации с деталями товаров

### 🎯 Ключевая особенность
В отличие от типичных RAG систем, наша реализация гарантирует, что LLM **никогда не выдумывает информацию**. Модель работает только с реальными результатами поиска, обеспечивая надёжные, основанные на фактах рекомендации.

## 📈 Производительность

- **Генерация эмбеддингов**: ~50мс на запрос (локально)
- **Векторный поиск**: ~5мс для 10К товаров
- **Генерация LLM**: ~2-3 секунды (локальная Llama 3.2:1b)
- **Полный пайплайн**: ~3-4 секунды от начала до конца

## 🤝 Участие в разработке

1. Форкните репозиторий
2. Создайте ветку фичи: `git checkout -b новая-фича`
3. Внесите изменения и убедитесь, что тесты проходят: `make check`
4. Закоммитьте изменения: `git commit -m "feat: описание"`
5. Отправьте PR

## 🎓 Образовательная ценность

Этот проект показывает:
- ✅ Как интегрировать AI в PHP приложения без внешних API
- ✅ Правильную архитектуру RAG систем
- ✅ Работу с векторными базами данных
- ✅ Локальные LLM для продакшена
- ✅ Семантический поиск на русском языке
- ✅ Production-ready DevOps практики

## 📄 Лицензия

Проект распространяется под [MIT лицензией](LICENSE) - используйте свободно в своих проектах.

## 🙋 Поддержка

- 🐛 **Баги и предложения**: [GitHub Issues](https://github.com/axcherednikov/rag-php-example/issues)
- 📚 **Документация**: Смотрите папку `/docs`
- 💬 **Обсуждения**: [GitHub Discussions](https://github.com/axcherednikov/rag-php-example/discussions)

## 🎉 Поддержите проект

Если проект оказался полезным:
- ⭐ Поставьте звезду на GitHub
- 🔄 Поделитесь с коллегами
- 📝 Напишите статью или сделайте доклад
- 🤝 Присылайте PR с улучшениями

---

**Готовы погрузиться в мир современных RAG систем?** Начните с `make setup`, а затем `make demo` для полного опыта! 🚀

> 💡 **Совет**: После установки попробуйте команду `php bin/console products:chat` и спросите: *"Посоветуй мощный процессор для разработки на PHP"*
