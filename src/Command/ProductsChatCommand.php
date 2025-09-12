<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ImprovedRAGService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'products:chat',
    description: 'Interactive AI chat for product search - demo for presentation',
)]
final class ProductsChatCommand extends Command
{
    private const SESSION_ID = 'chat_session';

    public function __construct(
        private readonly ImprovedRAGService $ragService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Interactive AI chat for product search - demo for presentation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        putenv('KMP_DUPLICATE_LIB_OK=TRUE');

        $this->showWelcome($io);

        while (true) {
            $query = $io->ask('Что вы ищете? ("выход" для завершения)');

            if (!$query || in_array(strtolower(trim($query)), ['выход', 'exit', 'quit', 'q'])) {
                break;
            }

            try {
                $this->handleSearchQuery($query, $io);
            } catch (\Exception $e) {
                $io->error($e->getMessage());
            }
        }

        return Command::SUCCESS;
    }

    private function showWelcome(SymfonyStyle $io): void
    {
        $io->title('AI Консультант');
    }

    private function handleSearchQuery(string $query, SymfonyStyle $io): void
    {
        try {
            $result = $this->ragService->searchWithContext($query, self::SESSION_ID);

            if ($result->hasResults()) {
                $this->displayChatResults($result->documents, $result->aiResponse, $io);
            } else {
                $io->warning('Товары не найдены');
            }
        } catch (\Exception $e) {
            $io->error('Ошибка поиска: '.$e->getMessage());
        }
    }

    /**
     * @param array<int, array<string, mixed>> $results
     */
    private function displayChatResults(array $results, string $aiResponse, SymfonyStyle $io): void
    {
        $io->section('Рекомендации');

        // Очищаем markdown и форматируем для консоли
        $cleanResponse = strip_tags(str_replace(['**', '*', '#', '`'], '', $aiResponse));
        $io->text($cleanResponse);

        $io->section('Найденные товары');

        foreach ($results as $i => $result) {
            if (!isset($result['payload']) || !isset($result['score'])) {
                continue;
            }

            $payload = $result['payload'];
            $price = isset($payload['price']) ? number_format($payload['price'] / 100, 0, '.', ' ') : 'Н/Д';
            $name = $payload['name'] ?? 'Товар без названия';
            $brand = $payload['brand'] ?? 'Н/Д';
            $category = $payload['category'] ?? 'Н/Д';
            $description = $payload['description'] ?? 'Описание отсутствует';

            $io->block([
                ($i + 1).". {$name}",
                "Цена: {$price} ₽",
                "{$brand} | {$category}",
                $description,
            ]);
        }
    }
}
