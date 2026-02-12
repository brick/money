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
            self::assertTrue($moneyBag->getMoney($currencyCode)->isZero());
        }
    }

    public function testAddSubtractMoney(): MoneyBag
    {
        $moneyBag = new MoneyBag();

        $moneyBag->add(Money::of('123', 'EUR'));
        self::assertMoneyBagContains([Money::of('123.00', 'EUR')], $moneyBag);

        $moneyBag->add(Money::of('234.99', 'EUR'));
        self::assertMoneyBagContains([Money::of('357.99', 'EUR')], $moneyBag);

        $moneyBag->add(Money::of(3, 'JPY'));
        self::assertMoneyBagContains([Money::of('357.99', 'EUR'), Money::of('3', 'JPY')], $moneyBag);

        $moneyBag->add(Money::of('1.1234', 'JPY', new AutoContext()));
        self::assertMoneyBagContains([Money::of('357.99', 'EUR'), Money::of('4.1234', 'JPY', new AutoContext())], $moneyBag);

        $moneyBag->subtract(Money::of('3.589950', 'EUR', new AutoContext()));
        self::assertMoneyBagContains([Money::of('354.400050', 'EUR', new AutoContext()), Money::of('4.1234', 'JPY', new AutoContext())], $moneyBag);

        $moneyBag->add(RationalMoney::of('1/3', 'EUR'));
        self::assertMoneyBagContains([RationalMoney::of('21284003/60000', 'EUR'), Money::of('4.1234', 'JPY', new AutoContext())], $moneyBag);

        return $moneyBag;
    }

    #[Depends('testAddSubtractMoney')]
    public function testAddCustomCurrency(MoneyBag $moneyBag): void
    {
        $moneyBag->add(Money::of('0.1234', new Currency('BTC', 0, 'Bitcoin', 8)));
        self::assertMoneyBagContains(
            [
                RationalMoney::of('21284003/60000', 'EUR'),
                Money::of('4.1234', 'JPY', new AutoContext()),
                Money::of('0.1234', new Currency('BTC', 0, 'Bitcoin', 8)),
            ],
            $moneyBag,
        );
    }

    public function testFromMonies(): void
    {
        $moneyBag = MoneyBag::fromMonies(
            Money::of('123', 'EUR'),
            Money::of('234.99', 'EUR'),
            Money::of(3, 'JPY'),
        );

        self::assertMoneyBagContains([Money::of('357.99', 'EUR'), Money::of('3', 'JPY')], $moneyBag);
    }

    public function testPlusMinusMoney(): MoneyBag
    {
        $moneyBag = MoneyBag::zero();
        self::assertMoneyBagContains([], $moneyBag);

        $moneyBag = $moneyBag->plus(Money::of('123', 'EUR'));
        self::assertMoneyBagContains([Money::of('123.00', 'EUR')], $moneyBag);

        $moneyBag = $moneyBag->plus(Money::of('234.99', 'EUR'));
        self::assertMoneyBagContains([Money::of('357.99', 'EUR')], $moneyBag);

        $moneyBag = $moneyBag->plus(Money::of(3, 'JPY'));
        self::assertMoneyBagContains([Money::of('357.99', 'EUR'), Money::of('3', 'JPY')], $moneyBag);

        $moneyBag = $moneyBag->plus(Money::of('1.1234', 'JPY', new AutoContext()));
        self::assertMoneyBagContains([Money::of('357.99', 'EUR'), Money::of('4.1234', 'JPY', new AutoContext())], $moneyBag);

        $moneyBag = $moneyBag->minus(Money::of('3.589950', 'EUR', new AutoContext()));
        self::assertMoneyBagContains([Money::of('354.400050', 'EUR', new AutoContext()), Money::of('4.1234', 'JPY', new AutoContext())], $moneyBag);

        $moneyBag = $moneyBag->plus(RationalMoney::of('1/3', 'EUR'));
        self::assertMoneyBagContains([RationalMoney::of('21284003/60000', 'EUR'), Money::of('4.1234', 'JPY', new AutoContext())], $moneyBag);

        return $moneyBag;
    }

    #[Depends('testPlusMinusMoney')]
    public function testPlusCustomCurrency(MoneyBag $moneyBag): void
    {
        $moneyBag = $moneyBag->plus(Money::of('0.1234', new Currency('BTC', 0, 'Bitcoin', 8)));
        self::assertMoneyBagContains(
            [
                RationalMoney::of('21284003/60000', 'EUR'),
                Money::of('4.1234', 'JPY', new AutoContext()),
                Money::of('0.1234', new Currency('BTC', 0, 'Bitcoin', 8)),
            ],
            $moneyBag,
        );
    }

    public function testImmutablePlus(): void
    {
        $moneyBag = MoneyBag::zero();
        $moneyBag = $moneyBag->plus(Money::of(20, 'EUR'));

        $newMoneyBag = $moneyBag->plus(Money::of(5, 'EUR'));

        self::assertMoneyBagContains([Money::of(20, 'EUR')], $moneyBag);
        self::assertMoneyBagContains([Money::of(25, 'EUR')], $newMoneyBag);
    }

    public function testImmutableMinus(): void
    {
        $moneyBag = MoneyBag::zero();
        $moneyBag = $moneyBag->plus(Money::of(20, 'EUR'));

        $newMoneyBag = $moneyBag->minus(Money::of(5, 'EUR'));

        self::assertMoneyBagContains([Money::of(20, 'EUR')], $moneyBag);
        self::assertMoneyBagContains([Money::of(15, 'EUR')], $newMoneyBag);
    }
}
