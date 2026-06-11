<?php

declare(strict_types=1);

namespace Tests;

use App\Domain\AccountRepository;
use App\Domain\Account;
use PHPUnit\Framework\TestCase;
use App\Domain\AccountService;
use App\Domain\Exception\AccountNotFoundException;

class AccountRepositoryTest extends TestCase
{
    // balance

    public function testGetBalanceWithNoAccount()
    {
        $repository = new AccountRepository();
        $service = new AccountService($repository);


        $this->expectException(AccountNotFoundException::class);
        $service->balance('1234');
    }

    public function testGetBalanceWithAccount(): void
    {
        $account = new Account('1', 50);
        $repository = new AccountRepository();
        $service = new AccountService($repository);

        $repository->save($account);

        $this->assertEquals(50, $service->balance('1'));
    }

    // withdraw

    public function testWithdrawWithNoAccount(): void
    {
        $repository = new AccountRepository();
        $service = new AccountService($repository);
        $this->expectException(AccountNotFoundException::class);
        $service->withdraw('1234', 40);
    }

    public function testWithdrawWithAccount(): void
    {
        $account = new Account('2', 50);
        $repository = new AccountRepository();
        $service = new AccountService($repository);

        $repository->save($account);
        $service->withdraw('2', 30);

        $this->assertEquals(20, $service->balance('2'));

        $this->assertSame(20, $repository->find('2')?->balance());


    }

    // deposit

    public function testGetBalanceCreatingAccount(): void
    {
        $repository = new AccountRepository();
        $service = new AccountService($repository);

        $service->deposit('1', 100);

        $this->assertEquals(100, $service->balance('1'));

        $this->assertSame(100, $repository->find('1')?->balance());

    }

    public function testTwoDepositsInARoll(): void
    {
        $repository = new AccountRepository();
        $service = new AccountService($repository);
        $service->deposit('3', 10);
        $service->deposit('3', 10);

        $this->assertSame(20, $service->balance('3'));
    }

    // transfer

    public function testTransWithNoDestin(): void
    {
        $originAcc = new Account('1', 50);
        $repository = new AccountRepository();
        $service = new AccountService($repository);
        $repository->save($originAcc);
        $service->transfer($originAcc->id(), '2', 10);

        $this->assertEquals(10, $service->balance('2'));
        $this->assertEquals(40, $service->balance('1'));
    }

    public function testTransWithNoOrigin(): void
    {
        $repository = new AccountRepository();
        $service = new AccountService($repository);
        $this->expectException(AccountNotFoundException::class);
        $service->transfer('1', '2', 10);
    }

    public function testTransWithOriginAndDest(): void
    {
        $originAcc = new Account('1', 50);
        $destAcc = new Account('2', 0);
        $repository = new AccountRepository();
        $service = new AccountService($repository);
        $repository->save($originAcc);
        $repository->save($destAcc);
        $service->transfer($originAcc->id(), $destAcc->id(), 10);

        $this->assertSame(40, $service->balance('1'));
        $this->assertSame(10, $service->balance('2'));
    }

    public function testTransferWithInsufficientFoundsLeavesBothUntoched(): void
    {
        $originAcc = new Account('1', 5);
        $destAcc = new Account('2', 50);
        $repository = new AccountRepository();
        $service = new AccountService($repository);
        $repository->save($originAcc);
        $repository->save($destAcc);

        try {
            $service->transfer($originAcc->id(), $destAcc->id(), 15);
            self::fail('Expected InsufficientFundsException.');
        } catch (\App\Domain\Exception\InsufficientFundsException) {
            $this->assertSame(5, $service->balance($originAcc->id()));
            $this->assertSame(50, $service->balance($destAcc->id()));
        }
    }
}
