<?php

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\Money;
use Brick\Money\MoneyBag;

/**
 * Tests for class MoneyBag.
 */
class MoneyBagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array    $expectedMonies
     * @param MoneyBag $moneyBag
     */
    private function assertMoneyBagContains(array $expectedMonies, $moneyBag)
    {
        $this->assertInstanceOf(MoneyBag::class, $moneyBag);

        // Test get() on each Money
        foreach ($expectedMonies as $money) {
            $money = Money::parse($money);
            $this->assertTrue($moneyBag->get($money->getCurrency())->isEqualTo($money));
        }

        $actualMonies = $moneyBag->getMonies();

        foreach ($actualMonies as & $money) {
            $money = (string) $money;
        }

        sort($expectedMonies);
        sort($actualMonies);

        // Test getMonies()
        $this->assertSame($expectedMonies, $actualMonies);
    }

    public function testNewMoneyBagIsEmpty()
    {
        $moneyBag = new MoneyBag();

        $this->assertMoneyBagContains([], $moneyBag);

        $this->assertSame('USD 0.00', (string) $moneyBag->get(Currency::of('USD')));
        $this->assertSame('EUR 0.00', (string) $moneyBag->get(Currency::of('EUR')));
        $this->assertSame('GBP 0.00', (string) $moneyBag->get(Currency::of('GBP')));
        $this->assertSame('JPY 0', (string) $moneyBag->get(Currency::of('JPY')));
    }

    public function testAddSubtractMoney()
    {
        $moneyBag = new MoneyBag();

        $moneyBag->add(Money::of('123', 'EUR'));
        $this->assertMoneyBagContains(['EUR 123.00'], $moneyBag);

        $moneyBag->add(Money::of('234.99', 'EUR'));
        $this->assertMoneyBagContains(['EUR 357.99'], $moneyBag);

        $moneyBag->add(Money::of(3, 'JPY'));
        $this->assertMoneyBagContains(['EUR 357.99', 'JPY 3'], $moneyBag);

        $moneyBag->add(Money::parse('JPY 1.1234'));
        $this->assertMoneyBagContains(['EUR 357.99', 'JPY 4.1234'], $moneyBag);

        $moneyBag->subtract(Money::parse('EUR 3.589950'));
        $this->assertMoneyBagContains(['EUR 354.400050', 'JPY 4.1234'], $moneyBag);
    }
}
