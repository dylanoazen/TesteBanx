<?php

declare(strict_types=1);

namespace Tests;

use App\Domain\Account;
use App\Domain\AccountRepository;
use App\Application\AccountService;
use App\Domain\Exception\AccountNotFoundException;
use App\Domain\Exception\InsufficientFundsException;
use PHPUnit\Framework\TestCase;

class AccountServiceTest extends TestCase
{
    private AccountRepository $repository;
    private AccountService $service;

    protected function setUp(): void
    {
        $this->repository = new AccountRepository();
        $this->service = new AccountService($this->repository);
    }

    // balance

    public function testGetBalanceWithNoAccount(): void
    {
        $this->expectException(AccountNotFoundException::class);
        $this->service->balance('1234');
    }

    public function testGetBalanceWithAccount(): void
    {
        $account = new Account('1', 50);
        $this->repository->save($account);

        $this->assertEquals(50, $this->service->balance('1'));
    }

    // withdraw

    public function testWithdrawWithNoAccount(): void
    {
        $this->expectException(AccountNotFoundException::class);
        $this->service->withdraw('1234', 40);
    }

    public function testWithdrawWithAccount(): void
    {
        $account = new Account('2', 50);
        $this->repository->save($account);
        $this->service->withdraw('2', 30);

        $this->assertEquals(20, $this->service->balance('2'));
        $this->assertSame(20, $this->repository->find('2')?->balance());
    }

    // deposit

    public function testGetBalanceCreatingAccount(): void
    {
        $this->service->deposit('1', 100);

        $this->assertEquals(100, $this->service->balance('1'));
        $this->assertSame(100, $this->repository->find('1')?->balance());
    }

    public function testTwoDepositsInARoll(): void
    {
        $this->service->deposit('3', 10);
        $this->service->deposit('3', 10);

        $this->assertSame(20, $this->service->balance('3'));
    }

    // transfer

    public function testTransWithNoDestin(): void
    {
        $originAcc = new Account('1', 50);
        $this->repository->save($originAcc);
        $this->service->transfer($originAcc->id(), '2', 10);

        $this->assertEquals(10, $this->service->balance('2'));
        $this->assertEquals(40, $this->service->balance('1'));
    }

    public function testTransWithNoOrigin(): void
    {
        $this->expectException(AccountNotFoundException::class);
        $this->service->transfer('1', '2', 10);
    }

    public function testTransWithOriginAndDest(): void
    {
        $originAcc = new Account('1', 50);
        $destAcc = new Account('2', 0);
        $this->repository->save($originAcc);
        $this->repository->save($destAcc);
        $this->service->transfer($originAcc->id(), $destAcc->id(), 10);

        $this->assertSame(40, $this->service->balance('1'));
        $this->assertSame(10, $this->service->balance('2'));
    }

    public function testTransferWithInsufficientFoundsLeavesBothUntoched(): void
    {
        $originAcc = new Account('1', 5);
        $destAcc = new Account('2', 50);
        $this->repository->save($originAcc);
        $this->repository->save($destAcc);

        try {
            $this->service->transfer($originAcc->id(), $destAcc->id(), 15);
            self::fail('Expected InsufficientFundsException.');
        } catch (InsufficientFundsException) {
            $this->assertSame(5, $this->service->balance($originAcc->id()));
            $this->assertSame(50, $this->service->balance($destAcc->id()));
        }
    }
}
