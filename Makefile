.PHONY: help install start stop restart logs clean setup demo

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Install PHP dependencies
	composer install

start: ## Start all services with Docker Compose
	docker-compose up -d

stop: ## Stop all services
	docker-compose down

restart: ## Restart all services
	docker-compose restart

logs: ## Show logs for all services
	docker-compose logs -f

logs-qdrant: ## Show Qdrant logs
	docker-compose logs -f qdrant

logs-ollama: ## Show Ollama logs
	docker-compose logs -f ollama

clean: ## Clean up Docker resources
	docker-compose down -v
	docker system prune -f

setup: ## Complete setup - install deps, start services, download models
	make install
	make start
	sleep 10
	docker-compose exec ollama ollama pull llama3.2:1b
	php bin/console products:vectorize

demo: ## Run interactive RAG demo
	php bin/console rag:demo --interactive

search: ## Run search demo (usage: make search QUERY="your query")
	php bin/console products:search "$(QUERY)"

chat: ## Start interactive chat
	php bin/console products:chat

check: ## Run static analysis and code style checks
	composer check

fix: ## Fix code style issues
	composer fix

test-qdrant: ## Test Qdrant connection
	curl -s http://localhost:6333/health | jq

test-ollama: ## Test Ollama connection
	curl -s http://localhost:11434/api/tags | jq