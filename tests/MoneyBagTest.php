<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Money\Context\AutoContext;
use Brick\Money\Currency;
use Brick\Money\Money;
use Brick\Money\MoneyBag;
use Brick\Money\RationalMoney;
use PHPUnit\Framework\Attributes\Depends;

/**
 * Tests for class MoneyBag.
 */
class MoneyBagTest extends AbstractTestCase
{
    public function testEmptyMoneyBag(): void
    {
        $moneyBag = new MoneyBag();

        self::assertMoneyBagContains([], $moneyBag);

        foreach (['USD', 'EUR', 'GBP', 'JPY'] as $currencyCode) {
            self::assertTrue($moneyBag->getAmount($currencyCode)->isZero());
        }
    }

    public function testAddSubtractMoney(): MoneyBag
    {
        $moneyBag = new MoneyBag();

        $moneyBag->add(Money::of('123', 'EUR'));
        self::assertMoneyBagContains(['EUR' => '123.00'], $moneyBag);

        $moneyBag->add(Money::of('234.99', 'EUR'));
        self::assertMoneyBagContains(['EUR' => '357.99'], $moneyBag);

        $moneyBag->add(Money::of(3, 'JPY'));
        self::assertMoneyBagContains(['EUR' => '357.99', 'JPY' => '3'], $moneyBag);

        $moneyBag->add(Money::of('1.1234', 'JPY', new AutoContext()));
        self::assertMoneyBagContains(['EUR' => '357.99', 'JPY' => '4.1234'], $moneyBag);

        $moneyBag->subtract(Money::of('3.589950', 'EUR', new AutoContext()));
        self::assertMoneyBagContains(['EUR' => '354.400050', 'JPY' => '4.1234'], $moneyBag);

        $moneyBag->add(RationalMoney::of('1/3', 'EUR'));
        self::assertMoneyBagContains(['EUR' => '21284003/60000', 'JPY' => '4.1234'], $moneyBag);

        return $moneyBag;
    }

    #[Depends('testAddSubtractMoney')]
    public function testAddCustomCurrency(MoneyBag $moneyBag): void
    {
        $moneyBag->add(Money::of('0.1234', new Currency('BTC', 0, 'Bitcoin', 8)));
        self::assertMoneyBagContains(['EUR' => '21284003/60000', 'JPY' => '4.1234', 'BTC' => '0.1234'], $moneyBag);
    }
}
