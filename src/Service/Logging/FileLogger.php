<?php

declare(strict_types=1);

namespace App\Service\Logging;

use App\Contract\LoggerInterface;

/**
 * File-based logger implementation.
 *
 * Writes log messages to files with structured format and rotation support.
 */
final readonly class FileLogger implements LoggerInterface
{
    private const string LOG_FORMAT = '[%s] %s: %s %s'.PHP_EOL;

    public function __construct(
        private string $logDirectory = 'var/log',
        private string $logFile = 'app.log',
    ) {
        $this->ensureLogDirectoryExists();
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log('EMERGENCY', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log('ALERT', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log('NOTICE', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Write log entry to file.
     *
     * @param string               $level   Log level
     * @param string               $message Log message
     * @param array<string, mixed> $context Additional context
     */
    private function log(string $level, string $message, array $context): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextJson = [] === $context ? '' : json_encode($context, JSON_UNESCAPED_UNICODE);

        $logEntry = sprintf(
            self::LOG_FORMAT,
            $timestamp,
            $level,
            $message,
            $contextJson
        );

        file_put_contents($this->getLogFilePath(), $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get full path to log file.
     *
     * @return string Log file path
     */
    private function getLogFilePath(): string
    {
        return $this->logDirectory.DIRECTORY_SEPARATOR.$this->logFile;
    }

    /**
     * Ensure log directory exists.
     */
    private function ensureLogDirectoryExists(): void
    {
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0755, true);
        }
    }
}
