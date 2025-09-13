<?php

declare(strict_types=1);

namespace App\Command;

use App\DTO\RAGSearchResult;
use App\Service\ImprovedRAGService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rag:demo',
    description: 'Демонстрация улучшенной RAG архитектуры с четким разделением этапов'
)]
final class RAGDemoCommand extends Command
{
    public function __construct(
        private readonly ImprovedRAGService $ragService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('query', null, InputOption::VALUE_REQUIRED, 'Поисковый запрос для демонстрации')
            ->addOption('interactive', 'i', InputOption::VALUE_NONE, 'Интерактивный режим чата');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->checkServices($io)) {
            return Command::FAILURE;
        }
        $interactive = $input->getOption('interactive');
        $query = $input->getOption('query');
        if ($interactive) {
            return $this->runInteractiveMode($io);
        }

        if ($query) {
            return $this->runSingleQuery($query, $io);
        }
        $this->showUsageExamples($io);

        return Command::SUCCESS;
    }

    private function checkServices(SymfonyStyle $io): bool
    {
        $status = $this->ragService->healthCheck();

        if (!$status['overall']) {
            $io->error('Сервисы недоступны');

            return false;
        }

        return true;
    }

    private function runSingleQuery(string $query, SymfonyStyle $io): int
    {
        try {
            $result = $this->ragService->search($query);
            $this->displayRAGSteps($result, $io);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function runInteractiveMode(SymfonyStyle $io): int
    {
        while (true) {
            $query = $io->ask('Ваш запрос ("выход" для завершения)');

            if (!$query || in_array(strtolower(trim((string) $query)), ['выход', 'exit', 'quit', 'q'])) {
                break;
            }

            try {
                $sessionId = 'interactive_'.time();
                $result = $this->ragService->searchWithContext($query, $sessionId);
                $this->displayCompactResult($result, $io);
            } catch (\Exception $e) {
                $io->error($e->getMessage());
            }
        }

        return Command::SUCCESS;
    }

    private function displayRAGSteps(RAGSearchResult $result, SymfonyStyle $io): void
    {
        $io->section('Query Processing');
        $io->definitionList(
            ['Исходный запрос' => $result->originalQuery],
            ['Оптимизированный термин' => $result->optimizedQuery]
        );

        $io->section('Retrieval');
        if ($result->hasResults()) {
            $io->text("Найдено товаров: {$result->getDocumentCount()}");

            $io->table(
                ['#', 'Товар', 'Бренд', 'Цена', 'Релевантность'],
                array_map(function ($doc, $i) {
                    $payload = $doc['payload'];
                    $price = number_format($payload['price'] / 100, 0, '.', ' ').' ₽';
                    $relevance = round($doc['score'] * 100, 1).'%';

                    return [$i + 1, $payload['name'], $payload['brand'], $price, $relevance];
                }, $result->documents, array_keys($result->documents))
            );
        } else {
            $io->warning('Товары не найдены');
        }

        $io->section('Generation');
        if ($result->hasResults()) {
            $io->block($result->aiResponse, null, 'fg=cyan');
        } else {
            $io->text('Генерация не выполнена');
        }
    }

    private function displayCompactResult(RAGSearchResult $result, SymfonyStyle $io): void
    {
        if ($result->hasResults()) {
            $io->text('AI Рекомендация:');
            $io->block($result->aiResponse, null, 'fg=green');
        } else {
            $io->warning('Товары не найдены');
        }
    }

    private function showUsageExamples(SymfonyStyle $io): void
    {
        $io->section('Примеры использования');
        $examples = [
            'Разовый поиск' => 'php bin/console rag:demo --query "процессор AMD для игр"',
            'Интерактивный чат' => 'php bin/console rag:demo --interactive',
        ];

        $rows = [];
        foreach ($examples as $description => $command) {
            $rows[] = [$description, $command];
        }

        $io->table(['Описание', 'Команда'], $rows);
    }
}
