<?php

declare(strict_types=1);

namespace App\Command;

use Codewithkyrian\Transformers\Pipelines\Pipeline;

use function Codewithkyrian\Transformers\Pipelines\pipeline;

use Codewithkyrian\Transformers\Pipelines\Task;
use Qdrant\Config;
use Qdrant\Http\Transport;
use Qdrant\Models\PointsStruct;
use Qdrant\Models\PointStruct;
use Qdrant\Models\Request\CreateCollection;
use Qdrant\Models\Request\VectorParams;
use Qdrant\Models\VectorStruct;
use Qdrant\Qdrant;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\Psr18Client;

#[AsCommand(
    name: 'products:vectorize',
    description: 'Vectorize product data and store in Qdrant',
)]
final class ProductsVectorizeCommand extends Command
{
    private const COLLECTION_NAME = 'products';
    private const DATA_FILE = __DIR__.'/../../data/products.json';

    private Qdrant $qdrantClient;
    private Pipeline $embedder;

    protected function configure(): void
    {
        $this->setDescription('Vectorize product data and store in Qdrant');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        putenv('KMP_DUPLICATE_LIB_OK=TRUE');

        try {
            $this->initializeServices();
            $this->prepareCollection();
            $this->vectorizeAndStore($this->loadProductData());

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

    private function loadProductData(): \Generator
    {
        if (!file_exists(self::DATA_FILE)) {
            throw new \RuntimeException('Products data file not found');
        }

        $json = file_get_contents(self::DATA_FILE);
        if (false === $json) {
            throw new \RuntimeException('Failed to read products data file');
        }

        $products = json_decode($json, true);

        if (!$products) {
            throw new \RuntimeException('Failed to parse products data');
        }

        foreach ($products as $product) {
            yield $product;
        }
    }

    private function prepareCollection(): void
    {
        try {
            $this->qdrantClient->collections(self::COLLECTION_NAME)->delete();
        } catch (\Exception $e) {
        }

        $createCollection = new CreateCollection();
        $createCollection->addVector(new VectorParams(1024, VectorParams::DISTANCE_COSINE), 'default');
        $this->qdrantClient->collections(self::COLLECTION_NAME)->create($createCollection);
    }

    private function vectorizeAndStore(\Generator $products): void
    {
        $batchSize = 50;
        $batch = [];

        foreach ($products as $product) {
            $batch[] = $product;

            if (count($batch) >= $batchSize) {
                $this->processBatch($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $this->processBatch($batch);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $batch
     */
    private function processBatch(array $batch): void
    {
        $pointsStruct = new PointsStruct();

        foreach ($batch as $product) {
            $text = $product['name'].' '.$product['description'];

            if (isset($product['specifications'])) {
                foreach ($product['specifications'] as $value) {
                    if (is_string($value)) {
                        $text .= ' '.$value;
                    }
                }
            }

            $text .= ' '.$product['brand'].' '.$product['category'];
            $embedding = ($this->embedder)($text, pooling: 'mean', normalize: true);

            $payload = [
                'name' => $product['name'],
                'category' => $product['category'],
                'brand' => $product['brand'],
                'price' => $product['price'],
                'description' => $product['description'],
            ];

            // Handle different embedding result types
            if (is_array($embedding)) {
                $vector = $embedding[0];
            } else {
                // For Tensor objects, convert to array and take first element
                $vector = $embedding instanceof \Codewithkyrian\Transformers\Tensor\Tensor ? $embedding[0] : [];
            }

            $pointsStruct->addPoint(
                new PointStruct(
                    $product['id'],
                    new VectorStruct($vector, 'default'),
                    $payload
                )
            );
        }

        $this->qdrantClient->collections(self::COLLECTION_NAME)->points()->upsert($pointsStruct);
    }
}
