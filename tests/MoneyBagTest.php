<?php

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\Money;
use Brick\Money\MoneyBag;

/**
 * Tests for class MoneyBag.
 */
class MoneyBagTest extends AbstractTestCase
{
    public function testNewMoneyBagIsEmpty()
    {
        $moneyBag = new MoneyBag();

        $this->assertMoneyBagContains([], $moneyBag);

        $this->assertMoneyIs('USD 0.00', $moneyBag->get(Currency::of('USD')));
        $this->assertMoneyIs('EUR 0.00', $moneyBag->get(Currency::of('EUR')));
        $this->assertMoneyIs('GBP 0.00', $moneyBag->get(Currency::of('GBP')));
        $this->assertMoneyIs('JPY 0', $moneyBag->get(Currency::of('JPY')));
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
