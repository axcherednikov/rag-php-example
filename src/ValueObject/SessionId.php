<?php

declare(strict_types=1);

namespace App\ValueObject;

/**
 * Value object representing a session identifier.
 *
 * Provides validation and type safety for session IDs
 * used throughout the application.
 */
final readonly class SessionId
{
    public function __construct(
        public string $value,
    ) {
        $this->validate();
    }

    /**
     * Create a new random session ID.
     *
     * @return self New session ID
     */
    public static function generate(): self
    {
        return new self(uniqid('session_', true));
    }

    /**
     * Create from string value.
     *
     * @param string $value Session ID string
     *
     * @return self Session ID value object
     *
     * @throws \InvalidArgumentException If value is invalid
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Get session ID as string.
     *
     * @return string Session ID value
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * String representation.
     *
     * @return string Session ID value
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Check equality with another session ID.
     *
     * @param SessionId $other Other session ID
     *
     * @return bool True if equal
     */
    public function equals(SessionId $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Validate session ID value.
     *
     * @throws \InvalidArgumentException If session ID is invalid
     */
    private function validate(): void
    {
        if ('' === $this->value || '0' === $this->value) {
            throw new \InvalidArgumentException('Session ID cannot be empty');
        }

        if (strlen($this->value) > 255) {
            throw new \InvalidArgumentException('Session ID is too long (max 255 characters)');
        }

        // Ensure session ID contains only safe characters
        if (in_array(preg_match('/^[a-zA-Z0-9_\-\.]+$/', $this->value), [0, false], true)) {
            throw new \InvalidArgumentException('Session ID contains invalid characters');
        }
    }
}
