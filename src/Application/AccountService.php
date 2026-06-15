<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Account;
use App\Domain\AccountRepository;
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

    public function transfer(string $originAccId, string $destAccId, int $amount): array
    {
        $originAccount = $this->findOrFail($originAccId);
        $destAccount = $this->repository->find($destAccId) ?? new Account($destAccId);
        $originAccount->withdraw($amount);
        $destAccount->deposit($amount);

        $this->repository->save($originAccount);
        $this->repository->save($destAccount);

        return ['origin' => $originAccount, 'destination' => $destAccount];
    }

    public function reset(): void
    {
        $this->repository->reset();
    }

}
