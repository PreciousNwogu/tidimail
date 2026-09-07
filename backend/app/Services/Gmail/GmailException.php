<?php

namespace App\Services\Gmail;

use RuntimeException;
use Throwable;

class GmailException extends RuntimeException
{
    public static function unauthorized(): self
    {
        return new self('Gmail access expired. Reconnect Google to continue.');
    }

    public static function insufficientScopes(): self
    {
        return new self('Google signed you in, but Gmail access was not granted. Sign in with Google again and allow Gmail.');
    }

    public static function busy(): self
    {
        return new self('Gmail asked us to slow down. Wait a minute, then try the scan again.');
    }

    public static function unavailable(): self
    {
        return new self('Gmail is busy right now. Try the scan again in a moment.');
    }

    public static function generic(): self
    {
        return new self('We could not finish reading Gmail. Try again in a moment.');
    }

    public static function sanitize(Throwable $exception): self
    {
        if ($exception instanceof self) {
            return $exception;
        }

        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'insufficient authentication scopes')) {
            return self::insufficientScopes();
        }

        if (str_contains($message, 'invalid_grant') || str_contains($message, 'unauthenticated') || str_contains($message, '401')) {
            return self::unauthorized();
        }

        if (str_contains($message, '429') || str_contains($message, 'rate limit') || str_contains($message, 'ratelimit')) {
            return self::busy();
        }

        if (str_contains($message, '500') || str_contains($message, '502') || str_contains($message, '503')) {
            return self::unavailable();
        }

        return self::generic();
    }
}
