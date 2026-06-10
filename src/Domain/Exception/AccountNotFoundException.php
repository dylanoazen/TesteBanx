<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class AccountNotFoundException extends \RuntimeException
{
    public function __construct(
        public readonly string $accountId,
    ) {
        parent::__construct(sprintf(
            'Account "%s" not found.',
            $accountId,
        ));
    }
}