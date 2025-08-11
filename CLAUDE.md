# RAG Vector Search with AI - Presentation Demo

This is a comprehensive demonstration of a modern RAG (Retrieval-Augmented Generation) system built with PHP, showcasing intelligent product search using vector embeddings and local AI models.

## System Overview

**Technologies:**
- PHP 8.3 + Symfony 7.3 (MicroKernelTrait)
- Qdrant Vector Database
- Transformers PHP (local embeddings)
- Ollama + Llama 3.2 (local AI model)
- Docker for services

**Key Features:**
- 🔍 **Semantic Search** - Vector similarity search in Russian/English
- 🤖 **AI Query Analysis** - Natural language understanding with Llama 3.2
- 💬 **Interactive Chat** - Conversational product discovery
- 🚀 **Memory Optimized** - Generator-based processing for large datasets
- 💰 **Cost Efficient** - 100% local, no API costs

## Demo Commands

### Basic Setup
```bash
docker-compose up -d                    # Start Qdrant
ollama pull llama3.2:1b                # Download AI model  
php bin/console products:vectorize      # Index products (one time)
```

### Search Demonstrations  
```bash
# Traditional search (English only)
php bin/console products:search "AMD processor"

# Russian search with translation
php bin/console products:search-ru "процессор AMD"

# AI-powered natural language search
php bin/console products:search-ai "ищу мощный процессор для игр"

# Interactive AI chat (main demo!)
php bin/console products:chat

# 🆕 NEW! Improved RAG Architecture Demo
php bin/console rag:demo --query "процессор AMD для игр"  # Single query with detailed steps
php bin/console rag:demo --interactive                     # Interactive RAG chat
```

## Architecture Highlights

### Traditional Pipeline
1. **Vector Embeddings**: Product descriptions → 384-dim vectors via all-MiniLM-L6-v2
2. **AI Query Processing**: Russian queries → optimized English search terms via Llama 3.2  
3. **Semantic Retrieval**: Cosine similarity search in Qdrant with configurable thresholds
4. **AI Response Generation**: Personalized recommendations based on search results

### 🆕 Improved RAG Architecture (Recommended by Curator)
**Three Clear Stages:**

1. **📝 Query Processing (Авторизация запроса)**
   - Natural language analysis with Llama 3.2
   - Query optimization for better search results  
   - Vector embedding generation (384-dimensional)

2. **🔍 Retrieval (Поиск в векторной БД)**
   - Semantic search in Qdrant vector database
   - Cosine similarity matching with relevance threshold
   - Context preparation for LLM

3. **✨ Generation (Ограниченная генерация)**
   - LLM works ONLY with retrieved documents
   - No "hallucination" - strictly based on found products
   - Constrained response generation with improved prompts

**Key Improvements**: 
- LLM never invents products or adds information "from itself" - it only works with the actual search results from the vector database
- **Critical**: All vectorization (both indexing and search) uses identical `Task::Embeddings` with `all-MiniLM-L6-v2` model for perfect semantic consistency
- Proper embedding consistency ensures accurate similarity matching between queries and stored products

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Dependency Management
- `composer install` - Install PHP dependencies
- `composer update` - Update dependencies

### Symfony Console
- `php bin/console` - Access Symfony console commands
- `php bin/console cache:clear` - Clear application cache
- `php bin/console debug:router` - Show all routes
- `php bin/console debug:container` - Debug service container

### Development Server
- `symfony serve` or `php -S localhost:8000 -t public/` - Start development server

### Docker Services
- `docker-compose up -d` - Start Qdrant vector database
- `docker-compose down` - Stop all services
- `docker-compose logs qdrant` - View Qdrant logs

### Qdrant Vector Database
- HTTP API: `http://localhost:6333`
- gRPC API: `localhost:6334`
- Web UI: `http://localhost:6333/dashboard`

## Architecture Overview

This is a Symfony 7.3 application using the MicroKernelTrait for minimal setup.

### Key Components
- **Kernel**: Uses `MicroKernelTrait` for streamlined configuration
- **Controllers**: Located in `src/Controller/` with attribute-based routing
- **Services**: Auto-configured in `config/services.yaml` with autowiring enabled
- **Configuration**: YAML-based configuration in `config/` directory

### Directory Structure
- `src/` - Application source code (PSR-4 autoloaded as `App\` namespace)
- `config/` - Application configuration files
- `public/` - Web root with front controller (`index.php`)
- `bin/console` - Symfony console entry point
- `var/cache/` - Application cache files
- `vendor/` - Composer dependencies

### Configuration Notes
- Services use autowiring and autoconfiguration by default
- Controllers are automatically registered via attribute routing
- Framework configuration is split across files in `config/packages/`
- Routes are configured in `config/routes.yaml` and via controller attributes