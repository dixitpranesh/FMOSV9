<?php

declare(strict_types=1);

namespace Fmos\Core;

final class AuthException extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $httpStatus = 403,
    ) {
        parent::__construct($message, $httpStatus);
    }
}
