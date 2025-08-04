<?php

declare(strict_types=1);

namespace InstagramClient\Exception;

use Exception;

class InstagramClientException extends Exception
{
    public static function invalidAccessToken(): self
    {
        return new self('Invalid or expired access token');
    }

    public static function apiError(string $message, int $code = 0): self
    {
        return new self("Instagram API Error: {$message}", $code);
    }

    public static function networkError(string $message): self
    {
        return new self("Network Error: {$message}");
    }
}
