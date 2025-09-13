<?php

declare(strict_types=1);

namespace App\Util;

/**
 * Utility class for environment setup and configuration.
 *
 * Centralizes common environment setup operations to eliminate code duplication
 * following the DRY (Don't Repeat Yourself) principle.
 */
final class EnvironmentSetup
{
    /**
     * Configure environment for transformers and machine learning libraries.
     *
     * Sets up environment variables to prevent OpenMP conflicts and other
     * common issues with ML libraries.
     */
    public static function configureMLEnvironment(): void
    {
        // Prevent OpenMP duplicate library loading issues
        if (in_array(getenv('KMP_DUPLICATE_LIB_OK'), ['', '0'], true) || false === getenv('KMP_DUPLICATE_LIB_OK')) {
            putenv('KMP_DUPLICATE_LIB_OK=TRUE');
        }

        // Set other ML-related environment variables if needed
        if (in_array(getenv('OMP_NUM_THREADS'), ['', '0'], true) || false === getenv('OMP_NUM_THREADS')) {
            putenv('OMP_NUM_THREADS=1');
        }
    }

    /**
     * Get environment variable with fallback.
     *
     * @param string $key     Environment variable name
     * @param string $default Default value if not set
     *
     * @return string Environment variable value or default
     */
    public static function getEnv(string $key, string $default = ''): string
    {
        $value = getenv($key);

        return false !== $value ? $value : $default;
    }

    /**
     * Check if we're in development environment.
     *
     * @return bool True if in development mode
     */
    public static function isDevelopment(): bool
    {
        return 'dev' === self::getEnv('APP_ENV', 'dev');
    }

    /**
     * Check if we're in production environment.
     *
     * @return bool True if in production mode
     */
    public static function isProduction(): bool
    {
        return 'prod' === self::getEnv('APP_ENV', 'dev');
    }

    /**
     * Get application debug status.
     *
     * @return bool True if debug mode is enabled
     */
    public static function isDebug(): bool
    {
        return '1' === self::getEnv('APP_DEBUG', '1');
    }
}
