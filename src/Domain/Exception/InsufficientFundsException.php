<?php
namespace App\Domain\Exception;

final class InsufficientFundsException extends \RuntimeException
{
    public function __construct(
        public readonly string $accountId,
        public readonly int $balance,       
        public readonly int $requested,     
    ) {
        parent::__construct(sprintf(
            'Account "%s" has insufficient funds: balance %d, requested %d.',
            $accountId, $balance, $requested,
        ));
    }
}
