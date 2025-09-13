<?php

declare(strict_types=1);

namespace App\Command;

use Codewithkyrian\Transformers\Pipelines\Pipeline;

use function Codewithkyrian\Transformers\Pipelines\pipeline;

use Codewithkyrian\Transformers\Pipelines\Task;
use Qdrant\Config;
use Qdrant\Http\Transport;
use Qdrant\Models\Request\SearchRequest;
use Qdrant\Models\VectorStruct;
use Qdrant\Qdrant;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\Psr18Client;

#[AsCommand(
    name: 'products:search',
    description: 'Search products using vector similarity',
)]
final class ProductsSearchCommand extends Command
{
    private const COLLECTION_NAME = 'products';

    private Qdrant $qdrantClient;
    private Pipeline $embedder;

    protected function configure(): void
    {
        $this
            ->addArgument('query', InputArgument::REQUIRED, 'Search query')
            ->setDescription('Search products using vector similarity');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $query = $input->getArgument('query');

        putenv('KMP_DUPLICATE_LIB_OK=TRUE');

        try {
            $this->initializeServices();
            $results = $this->searchProducts($query);
            $this->displayResults($results, $query, $io);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    private function initializeServices(): void
    {
        $config = new Config('http://localhost', 6333);
        $transport = new Transport(new Psr18Client(), $config);
        $this->qdrantClient = new Qdrant($transport);
        $this->embedder = pipeline(Task::Embeddings, 'onnx-community/Qwen3-Embedding-0.6B-ONNX');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchProducts(string $query): array
    {
        $embedding = ($this->embedder)($query, pooling: 'mean', normalize: true);

        if (is_array($embedding)) {
            $vector = $embedding[0];
        } else {
            $vector = $embedding instanceof \Codewithkyrian\Transformers\Tensor\Tensor ? $embedding[0] : [];
        }

        $searchVector = new VectorStruct($vector, 'default');
        $searchRequest = new SearchRequest($searchVector);
        $searchRequest->setLimit(5)->setWithPayload(true)->setScoreThreshold(0.5);

        $response = $this->qdrantClient->collections(self::COLLECTION_NAME)->points()->search($searchRequest);

        return $response['result'] ?? [];
    }

    /**
     * @param array<int, array<string, mixed>> $results
     */
    private function displayResults(array $results, string $query, SymfonyStyle $io): void
    {
        if (empty($results)) {
            $io->warning('No products found');

            return;
        }

        foreach ($results as $result) {
            $payload = $result['payload'];
            $score = $result['score'];

            $io->section(sprintf('%s (%.3f)', $payload['name'], $score));
            $io->text(sprintf('Brand: %s | Category: %s', $payload['brand'], $payload['category']));
            $io->text(sprintf('Price: %s ₽', number_format($payload['price'] / 100, 0, '.', ' ')));
            $io->text($payload['description']);
        }
    }
}
