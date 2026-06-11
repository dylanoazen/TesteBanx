<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\AccountNotFoundException;

final class AccountService
{
    public function __construct(private readonly AccountRepository $repository)
    {
    }

    private function findOrFail(string $accountId): Account
    {
        $account = $this->repository->find($accountId);

        if ($account === null) {
            throw new AccountNotFoundException($accountId);
        }

        return $account;

    }

    public function balance(string $accountId): int
    {
        return $this->findOrFail($accountId)->balance();
    }

    public function withdraw(string $originId, int $amount): Account
    {
        $account = $this->findOrFail($originId);
        $account->withdraw($amount);
        $this->repository->save($account);
        return $account;
    }

    public function deposit(string $destId, int $amount): Account
    {
        $account = $this->repository->find($destId) ?? new Account($destId);
        $account->deposit($amount);
        $this->repository->save($account);
        return $account;
    }

    public function reset(): void
    {
        $this->repository->reset();
    }

}
