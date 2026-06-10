<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Domain\Account;
use App\Domain\Exception\InsufficientFundsException;

class AccountTest extends TestCase {

    public function testNewAccountStartsWithGivenBalance(){
        $account = new Account('1', 100);
        $this->assertEquals(100, $account->balance());
    }
    
    public function testwithdrawWithSufficientFounds(){
        $account = new Account('1', 100);
        $account->withdraw(50);
        $this->assertEquals(50, $account->balance());
    }

    public function testwithdrawWithInsufficientFounds(){
        $account = new Account('1', 100);
        $this->expectException(InsufficientFundsException::class);
        $account->withdraw(150);
    }

    public function testDeposit(){
        $account = new Account('1', 100);
        $account->deposit(50);
        $this->assertEquals(150, $account->balance());
    }
}