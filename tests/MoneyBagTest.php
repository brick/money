<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Money\Context\AutoContext;
use Brick\Money\Currency;
use Brick\Money\Money;
use Brick\Money\MoneyBag;
use Brick\Money\RationalMoney;
use PHPUnit\Framework\Attributes\Depends;

use function json_encode;

/**
 * Tests for class MoneyBag.
 */
class MoneyBagTest extends AbstractTestCase
{
    public function testEmptyMoneyBag(): void
    {
        $moneyBag = MoneyBag::zero();

        self::assertMoneyBagContains([], $moneyBag);

        foreach (['USD', 'EUR', 'GBP', 'JPY'] as $currencyCode) {
            self::assertTrue($moneyBag->getMoney($currencyCode)->isZero());
        }
    }

    public function testAddSubtractMoney(): MoneyBag
    {
        $moneyBag = MoneyBag::zero();

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

    #[Depends('testAddSubtractMoney')]
    public function testAddCustomCurrency(MoneyBag $moneyBag): void
    {
        $moneyBag = $moneyBag->plus(Money::of('0.1234', new Currency('BTC', 0, 'Bitcoin', 8)));
        self::assertMoneyBagContains(
            [
                Money::of('0.1234', new Currency('BTC', 0, 'Bitcoin', 8)),
                RationalMoney::of('21284003/60000', 'EUR'),
                Money::of('4.1234', 'JPY', new AutoContext()),
            ],
            $moneyBag,
        );
    }

    public function testOf(): void
    {
        $moneyBag = MoneyBag::of(
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
                Money::of('0.1234', new Currency('BTC', 0, 'Bitcoin', 8)),
                RationalMoney::of('21284003/60000', 'EUR'),
                Money::of('4.1234', 'JPY', new AutoContext()),
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

    public function testZeroBalanceEntriesAreNotRetained(): void
    {
        $moneyBag = MoneyBag::zero()->plus(Money::zero('EUR'));
        self::assertMoneyBagContains([], $moneyBag);

        $moneyBag = MoneyBag::zero()
            ->plus(Money::of('5.00', 'EUR'))
            ->minus(Money::of('5.00', 'EUR'));
        self::assertMoneyBagContains([], $moneyBag);

        $moneyBag = MoneyBag::zero()
            ->plus(Money::of('5.00', 'EUR'))
            ->plus(Money::of('3', 'JPY'))
            ->minus(Money::of('5.00', 'EUR'));
        self::assertMoneyBagContains([Money::of('3', 'JPY')], $moneyBag);
    }

    public function testIsZero(): void
    {
        self::assertTrue(MoneyBag::zero()->isZero());

        $nonZero = MoneyBag::zero()->plus(Money::of('1.00', 'EUR'));
        self::assertFalse($nonZero->isZero());

        $backToZero = $nonZero->minus(Money::of('1.00', 'EUR'));
        self::assertTrue($backToZero->isZero());
    }

    public function testNegated(): void
    {
        $bag = MoneyBag::zero()
            ->plus(Money::of('10.00', 'EUR'))
            ->plus(RationalMoney::of('1/3', 'USD'));

        $result = $bag->negated();

        self::assertMoneyBagContains([RationalMoney::of('-10', 'EUR'), RationalMoney::of('-1/3', 'USD')], $result);
    }

    public function testNegatedIsImmutable(): void
    {
        $bag = MoneyBag::zero()->plus(Money::of('10.00', 'EUR'));
        $bag->negated();

        self::assertMoneyBagContains([Money::of('10.00', 'EUR')], $bag);
    }

    public function testNegatedOnEmptyBagReturnsEmptyBag(): void
    {
        self::assertMoneyBagContains([], MoneyBag::zero()->negated());
    }

    public function testMultipliedBy(): void
    {
        $bag = MoneyBag::zero()
            ->plus(Money::of('10.00', 'EUR'))
            ->plus(RationalMoney::of('1/3', 'USD'));

        $result = $bag->multipliedBy(3);

        self::assertMoneyBagContains([Money::of('30.00', 'EUR'), RationalMoney::of('1', 'USD')], $result);
    }

    public function testMultipliedByZeroReturnsEmptyBag(): void
    {
        $bag = MoneyBag::zero()
            ->plus(Money::of('10.00', 'EUR'))
            ->plus(Money::of('5', 'JPY'));

        self::assertMoneyBagContains([], $bag->multipliedBy(0));
    }

    public function testMultipliedByIsImmutable(): void
    {
        $bag = MoneyBag::zero()->plus(Money::of('10.00', 'EUR'));
        $bag->multipliedBy(2);

        self::assertMoneyBagContains([Money::of('10.00', 'EUR')], $bag);
    }

    public function testDividedBy(): void
    {
        $bag = MoneyBag::zero()
            ->plus(Money::of('9.00', 'EUR'))
            ->plus(Money::of('5', 'JPY'));

        $result = $bag->dividedBy(3);

        self::assertMoneyBagContains([RationalMoney::of('3', 'EUR'), RationalMoney::of('5/3', 'JPY')], $result);
    }

    public function testDividedByIsImmutable(): void
    {
        $bag = MoneyBag::zero()->plus(Money::of('9.00', 'EUR'));
        $bag->dividedBy(3);

        self::assertMoneyBagContains([Money::of('9.00', 'EUR')], $bag);
    }

    public function testDividedByZeroThrowsOnNonEmptyBag(): void
    {
        $bag = MoneyBag::zero()->plus(Money::of('9.00', 'EUR'));

        $this->expectException(DivisionByZeroException::class);
        $bag->dividedBy(0);
    }

    public function testDividedByZeroThrowsOnEmptyBag(): void
    {
        $this->expectException(DivisionByZeroException::class);
        MoneyBag::zero()->dividedBy(0);
    }

    public function testDividedByInvalidStringThrowsOnNonEmptyBag(): void
    {
        $bag = MoneyBag::zero()->plus(Money::of('9.00', 'EUR'));

        $this->expectException(NumberFormatException::class);
        $bag->dividedBy('garbage');
    }

    public function testDividedByInvalidStringThrowsOnEmptyBag(): void
    {
        $this->expectException(NumberFormatException::class);
        MoneyBag::zero()->dividedBy('garbage');
    }

    public function testIsEqualToTwoEmptyBags(): void
    {
        self::assertTrue(MoneyBag::zero()->isEqualTo(MoneyBag::zero()));
    }

    public function testIsEqualToSameSingleCurrency(): void
    {
        $a = MoneyBag::zero()->plus(Money::of('1.23', 'USD'));
        $b = MoneyBag::zero()->plus(Money::of('1.23', 'USD'));

        self::assertTrue($a->isEqualTo($b));
    }

    public function testIsEqualToSameMultipleCurrencies(): void
    {
        $a = MoneyBag::zero()
            ->plus(Money::of('1.23', 'USD'))
            ->plus(Money::of('4.56', 'EUR'));

        // Different insertion order must not matter (bag is sorted by currency code internally).
        $b = MoneyBag::zero()
            ->plus(Money::of('4.56', 'EUR'))
            ->plus(Money::of('1.23', 'USD'));

        self::assertTrue($a->isEqualTo($b));
    }

    public function testIsEqualToSameRationalAmounts(): void
    {
        $a = MoneyBag::zero()->plus(RationalMoney::of('1/3', 'EUR'));
        $b = MoneyBag::zero()->plus(RationalMoney::of('1/3', 'EUR'));

        self::assertTrue($a->isEqualTo($b));
    }

    public function testIsEqualToDifferentAmounts(): void
    {
        $a = MoneyBag::zero()->plus(Money::of('1.23', 'USD'));
        $b = MoneyBag::zero()->plus(Money::of('1.24', 'USD'));

        self::assertFalse($a->isEqualTo($b));
    }

    public function testIsEqualToDifferentCurrencies(): void
    {
        $a = MoneyBag::zero()->plus(Money::of('1.23', 'USD'));
        $b = MoneyBag::zero()->plus(Money::of('1.23', 'EUR'));

        self::assertFalse($a->isEqualTo($b));
    }

    public function testIsEqualToDifferentCurrencyCount(): void
    {
        $a = MoneyBag::zero()->plus(Money::of('1.23', 'USD'));
        $b = MoneyBag::zero()
            ->plus(Money::of('1.23', 'USD'))
            ->plus(Money::of('4.56', 'EUR'));

        self::assertFalse($a->isEqualTo($b));
    }

    public function testIsEqualToEmptyAndNonEmpty(): void
    {
        $empty = MoneyBag::zero();
        $nonEmpty = MoneyBag::zero()->plus(Money::of('1.00', 'USD'));

        self::assertFalse($empty->isEqualTo($nonEmpty));
        self::assertFalse($nonEmpty->isEqualTo($empty));
    }

    public function testJsonSerializeEmpty(): void
    {
        $moneyBag = MoneyBag::zero();

        self::assertSame([], $moneyBag->jsonSerialize());
        self::assertSame('[]', json_encode($moneyBag));
    }

    public function testJsonSerializeSingleCurrency(): void
    {
        $moneyBag = MoneyBag::zero()->plus(Money::of('1.23', 'USD'));

        $expected = [['amount' => '123/100', 'currency' => 'USD']];

        self::assertSame($expected, $moneyBag->jsonSerialize());
        self::assertSame(json_encode($expected), json_encode($moneyBag));
    }

    public function testJsonSerializeMultipleCurrencies(): void
    {
        $moneyBag = MoneyBag::zero()
            ->plus(Money::of('1.23', 'USD'))
            ->plus(RationalMoney::of('3/7', 'EUR'))
            ->plus(Money::of('100', 'JPY'));

        $expected = [
            ['amount' => '3/7', 'currency' => 'EUR'],
            ['amount' => '100', 'currency' => 'JPY'],
            ['amount' => '123/100', 'currency' => 'USD'],
        ];

        self::assertSame($expected, $moneyBag->jsonSerialize());
        self::assertSame(json_encode($expected), json_encode($moneyBag));
    }
}
