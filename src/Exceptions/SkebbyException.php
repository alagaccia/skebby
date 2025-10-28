<?php

namespace AndreaLagaccia\Skebby\Exceptions;

use Exception;

/**
 * Skebby API Exception
 * 
 * This exception is thrown when there are errors related to the Skebby API
 * operations such as authentication failures, API errors, or network issues.
 */
class SkebbyException extends Exception
{
    /**
     * Create a new Skebby exception instance
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param Exception|null $previous The previous exception
     */
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create an authentication exception
     *
     * @param string $message Custom error message
     * @return static
     */
    public static function authenticationFailed(string $message = 'Authentication failed'): self
    {
        return new static($message, 401);
    }

    /**
     * Create an API request exception
     *
     * @param string $message Custom error message
     * @param int $statusCode HTTP status code
     * @return static
     */
    public static function apiRequestFailed(string $message, int $statusCode = 500): self
    {
        return new static($message, $statusCode);
    }

    /**
     * Create a validation exception
     *
     * @param string $message Custom error message
     * @return static
     */
    public static function validationFailed(string $message): self
    {
        return new static($message, 422);
    }
}