<?php

declare(strict_types=1);

namespace App\Domain;

final class AccountRepository
{
    /** @var array<string, Account> */
    private array $accounts = [];

    public function find(string $id): ?Account
    {
        return $this->accounts[$id] ?? null;
    }

    public function save(Account $account): void
    {
        $this->accounts[$account->id()] = $account;
    }

    public function reset(): void
    {
        $this->accounts = [];
    }
}