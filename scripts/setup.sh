#!/bin/bash

set -e

echo "🚀 Setting up RAG Vector Search Demo..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_step() {
    echo -e "${BLUE}➤${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Check if Docker is running
if ! docker info >/dev/null 2>&1; then
    print_error "Docker is not running. Please start Docker and try again."
    exit 1
fi

# Check if jq is available
if ! command -v jq &> /dev/null; then
    print_warning "jq is not installed. Installing it for JSON processing..."
    if [[ "$OSTYPE" == "darwin"* ]]; then
        brew install jq
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        sudo apt-get update && sudo apt-get install -y jq
    fi
fi

# Install PHP dependencies
print_step "Installing PHP dependencies..."
composer install --optimize-autoloader
print_success "Dependencies installed"

# Start Docker services
print_step "Starting Docker services..."
docker-compose up -d
print_success "Docker services started"

# Wait for services to be ready
print_step "Waiting for services to initialize..."
sleep 15

# Check Qdrant health
print_step "Checking Qdrant connection..."
for i in {1..30}; do
    if curl -s http://localhost:6333/health >/dev/null 2>&1; then
        print_success "Qdrant is ready"
        break
    fi
    if [ $i -eq 30 ]; then
        print_error "Qdrant failed to start after 30 attempts"
        exit 1
    fi
    sleep 2
done

# Check Ollama connection
print_step "Checking Ollama connection..."
for i in {1..30}; do
    if curl -s http://localhost:11434/api/tags >/dev/null 2>&1; then
        print_success "Ollama is ready"
        break
    fi
    if [ $i -eq 30 ]; then
        print_error "Ollama failed to start after 30 attempts"
        exit 1
    fi
    sleep 2
done

# Download Ollama model
print_step "Downloading Llama 3.2 model (this may take a few minutes)..."
docker-compose exec -T ollama ollama pull llama3.2:1b
print_success "Llama 3.2 model downloaded"

# Download Transformers models
print_step "Downloading embedding models..."
if [ ! -d ".transformers-cache" ]; then
    mkdir -p .transformers-cache
fi
print_success "Embedding models cache prepared"

# Vectorize products
print_step "Vectorizing product data..."
php bin/console products:vectorize
print_success "Product data vectorized and indexed"

echo
echo -e "${GREEN}🎉 Setup complete!${NC}"
echo
echo "Available commands:"
echo -e "  ${BLUE}make demo${NC}       - Run interactive RAG demo"
echo -e "  ${BLUE}make search${NC}     - Search products (e.g., make search QUERY=\"gaming laptop\")"
echo -e "  ${BLUE}make chat${NC}       - Start interactive chat"
echo -e "  ${BLUE}make logs${NC}       - View service logs"
echo -e "  ${BLUE}make stop${NC}       - Stop all services"
echo
echo "Web interfaces:"
echo -e "  ${BLUE}Qdrant Dashboard${NC}: http://localhost:6333/dashboard"
echo -e "  ${BLUE}Ollama API${NC}:       http://localhost:11434"
echo