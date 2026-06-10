<?php


declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exception\InsufficientFundsException;

final class Account
{
    private readonly string $id;
    private int $balance;

    public function __construct(string $id, int $balance = 0)
    {
        $this->id = $id;
        $this->balance = $balance;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'balance' => $this->balance,
        ];
    }

    public function withdraw(int $amount): void
    {
        $this->guardPositive($amount);
        if ($amount > $this->balance) {
            throw new InsufficientFundsException($this->id, $this->balance, $amount);
        }
        $this->balance -= $amount;
    }

    public function balance(): int
    {
        return $this->balance;
    }

    public function deposit(int $amount): void
    {
        $this->guardPositive($amount);
        $this->balance += $amount;
    }

    private function guardPositive(int $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
    }
}
