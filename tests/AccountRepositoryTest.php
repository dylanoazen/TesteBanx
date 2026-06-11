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

    public function testGetBalanceWithAccount()
    {
        $account = new Account('1', 50);
        $repository = new AccountRepository();
        $service = new AccountService($repository);

        $repository->save($account);

        $this->assertEquals(50, $service->balance('1'));
    }

    // withdraw

    public function testWithdrawWithNoAccount()
    {
        $repository = new AccountRepository();
        $service = new AccountService($repository);
        $this->expectException(AccountNotFoundException::class);
        $service->withdraw('1234', 40);
    }

    public function testWithdrawWithAccount()
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

    public function testGetBalanceCreatingAccount()
    {
        $repository = new AccountRepository();
        $service = new AccountService($repository);

        $service->deposit('1', 100);

        $this->assertEquals(100, $service->balance('1'));

        $this->assertSame(100, $repository->find('1')?->balance());

    }

    public function testTwoDepositsInARoll()
    {
        $repository = new AccountRepository();
        $service = new AccountService($repository);
        $service->deposit('3', 10);
        $service->deposit('3', 10);

        $this->assertSame(20, $service->balance('3'));
    }

}
