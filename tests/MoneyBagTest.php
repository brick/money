<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Money\Context\AutoContext;
use Brick\Money\IsoCurrencyProvider;
use Brick\Money\Money;
use Brick\Money\MoneyBag;
use Brick\Money\RationalMoney;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Tests for class MoneyBag.
 */
class MoneyBagTest extends AbstractTestCase
{
    public function testEmptyMoneyBag() : void
    {
        $moneyBag = new MoneyBag();

        $this->assertMoneyBagContains([], $moneyBag);

        foreach (['USD', 'EUR', 'GBP', 'JPY'] as $currencyCode) {
            self::assertTrue($moneyBag->getAmount(IsoCurrencyProvider::getInstance()->getByCode($currencyCode))->isZero());
        }
    }

    public function testAddSubtractMoney() : MoneyBag
    {
        $moneyBag = new MoneyBag();

        $moneyBag->add(Money::of('123', 'EUR'));
        $this->assertMoneyBagContains(['EUR' => '123.00'], $moneyBag);

        $moneyBag->add(Money::of('234.99', 'EUR'));
        $this->assertMoneyBagContains(['EUR' => '357.99'], $moneyBag);

        $moneyBag->add(Money::of(3, 'JPY'));
        $this->assertMoneyBagContains(['EUR' => '357.99', 'JPY' => '3'], $moneyBag);

        $moneyBag->add(Money::of('1.1234', 'JPY', new AutoContext()));
        $this->assertMoneyBagContains(['EUR' => '357.99', 'JPY' => '4.1234'], $moneyBag);

        $moneyBag->subtract(Money::of('3.589950', 'EUR', new AutoContext()));
        $this->assertMoneyBagContains(['EUR' => '354.400050', 'JPY' => '4.1234'], $moneyBag);

        $moneyBag->add(RationalMoney::of('1/3', 'EUR'));
        $this->assertMoneyBagContains(['EUR' => '21284003/60000', 'JPY' => '4.1234'], $moneyBag);

        return $moneyBag;
    }

    #[Depends('testAddSubtractMoney')]
    public function testAddCustomCurrency(MoneyBag $moneyBag) : void
    {
        $moneyBag->add(Money::of('0.123', new CustomCurrency()));
        $this->assertMoneyBagContains(['EUR' => '21284003/60000', 'JPY' => '4.1234', 'CUSTOM' => '0.123'], $moneyBag);
    }
}
