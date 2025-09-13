<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Util\ValidationHelper;

/**
 * Value object representing a search query.
 *
 * Encapsulates query validation and normalization logic
 * following Domain-Driven Design principles.
 */
final readonly class SearchQuery
{
    public function __construct(
        public string $value,
    ) {
        $this->validate();
    }

    /**
     * Create from user input with normalization.
     *
     * @param string $input Raw user input
     *
     * @return self Normalized search query
     *
     * @throws \InvalidArgumentException If input is invalid
     */
    public static function fromUserInput(string $input): self
    {
        $normalized = ValidationHelper::sanitizeString($input);

        return new self($normalized);
    }

    /**
     * Check if query is empty.
     *
     * @return bool True if query is empty
     */
    public function isEmpty(): bool
    {
        return '' === $this->value || '0' === $this->value;
    }

    /**
     * Get query length in characters.
     *
     * @return int Character count
     */
    public function getLength(): int
    {
        return mb_strlen($this->value, 'UTF-8');
    }

    /**
     * Check if query contains specific term.
     *
     * @param string $term Term to search for
     *
     * @return bool True if term is found
     */
    public function contains(string $term): bool
    {
        return str_contains(mb_strtolower($this->value), mb_strtolower($term));
    }

    /**
     * Get query as string.
     *
     * @return string Query value
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * String representation.
     *
     * @return string Query value
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Validate query value.
     *
     * @throws \InvalidArgumentException If query is invalid
     */
    private function validate(): void
    {
        ValidationHelper::validateNotEmpty($this->value, 'search query');
        ValidationHelper::validateMaxLength($this->value, 1000, 'search query');
        ValidationHelper::validateSafeString($this->value, 'search query');
    }
}
