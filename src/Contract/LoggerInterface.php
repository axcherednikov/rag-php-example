<?php

declare(strict_types=1);

namespace App\Contract;

/**
 * Interface for application logging.
 *
 * Provides structured logging capabilities for the RAG system
 * with different severity levels and contextual information.
 */
interface LoggerInterface
{
    /**
     * Log emergency message.
     *
     * @param string $message Log message
     * @param array  $context Additional context data
     */
    public function emergency(string $message, array $context = []): void;

    /**
     * Log alert message.
     *
     * @param string $message Log message
     * @param array  $context Additional context data
     */
    public function alert(string $message, array $context = []): void;

    /**
     * Log critical error.
     *
     * @param string $message Log message
     * @param array  $context Additional context data
     */
    public function critical(string $message, array $context = []): void;

    /**
     * Log error message.
     *
     * @param string $message Log message
     * @param array  $context Additional context data
     */
    public function error(string $message, array $context = []): void;

    /**
     * Log warning message.
     *
     * @param string $message Log message
     * @param array  $context Additional context data
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Log notice message.
     *
     * @param string $message Log message
     * @param array  $context Additional context data
     */
    public function notice(string $message, array $context = []): void;

    /**
     * Log informational message.
     *
     * @param string $message Log message
     * @param array  $context Additional context data
     */
    public function info(string $message, array $context = []): void;

    /**
     * Log debug message.
     *
     * @param string $message Log message
     * @param array  $context Additional context data
     */
    public function debug(string $message, array $context = []): void;
}
