<?php

declare(strict_types=1);

namespace App\Contract;

/**
 * Interface for managing chat context and session data.
 *
 * Provides methods to store and retrieve conversation context
 * to improve search accuracy in multi-turn conversations.
 */
interface ContextServiceInterface
{
    /**
     * Store search context for a session.
     *
     * @param string $sessionId Unique session identifier
     * @param string $category  Product category inferred from search
     * @param string $query     Last successful query
     */
    public function setSearchContext(string $sessionId, string $category, string $query): void;

    /**
     * Get stored context for session.
     *
     * @param string $sessionId Unique session identifier
     *
     * @return string|null Category context or null if not found/expired
     */
    public function getSearchContext(string $sessionId): ?string;

    /**
     * Extract category from search results.
     *
     * @param array<int, array<string, mixed>> $searchResults Array of search results with payload
     *
     * @return string|null Extracted category or null
     */
    public function extractCategoryFromResults(array $searchResults): ?string;

    /**
     * Infer category from user query.
     *
     * @param string $query User query to analyze
     *
     * @return string|null Inferred category or null
     */
    public function inferCategoryFromQuery(string $query): ?string;

    /**
     * Get count of active sessions.
     *
     * @return int Number of active sessions
     */
    public function getActiveSessionsCount(): int;
}
