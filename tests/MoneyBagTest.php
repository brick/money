<?php

namespace Brick\Money\Tests;

use Brick\Money\Currency;
use Brick\Money\CurrencyConverter;
use Brick\Money\ExchangeRateProvider\ConfigurableExchangeRateProvider;
use Brick\Money\Money;
use Brick\Money\MoneyBag;
use Brick\Money\Context\DefaultContext;

use Brick\Math\RoundingMode;

/**
 * Tests for class MoneyBag.
 */
class MoneyBagTest extends AbstractTestCase
{
    /**
     * @return MoneyBag
     */
    public function testNewMoneyBagIsEmpty()
    {
        $moneyBag = new MoneyBag();

        $this->assertMoneyBagContains([], $moneyBag);

        $this->assertMoneyIs('USD 0.00', $moneyBag->get(Currency::of('USD')));
        $this->assertMoneyIs('EUR 0.00', $moneyBag->get(Currency::of('EUR')));
        $this->assertMoneyIs('GBP 0.00', $moneyBag->get(Currency::of('GBP')));
        $this->assertMoneyIs('JPY 0', $moneyBag->get(Currency::of('JPY')));

        return $moneyBag;
    }

    /**
     * @depends testNewMoneyBagIsEmpty
     *
     * @param MoneyBag $moneyBag
     *
     * @return MoneyBag
     */
    public function testAddSubtractMoney(MoneyBag $moneyBag)
    {
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

        return $moneyBag;
    }

    /**
     * @depends testAddSubtractMoney
     *
     * @param MoneyBag $moneyBag
     */
    public function testValue(MoneyBag $moneyBag)
    {
        $exchangeRateProvider = new ConfigurableExchangeRateProvider();
        $exchangeRateProvider->setExchangeRate('EUR', 'USD', '1.23456789');
        $exchangeRateProvider->setExchangeRate('JPY', 'USD', '0.00987654321');

        $context = new DefaultContext();
        $currencyConverter = new CurrencyConverter($exchangeRateProvider, $context, RoundingMode::DOWN);
        $this->assertMoneyIs('USD 437.57', $moneyBag->getValue(Currency::of('USD'), $currencyConverter));

        $context = new DefaultContext();
        $currencyConverter = new CurrencyConverter($exchangeRateProvider, $context, RoundingMode::UP);
        $this->assertMoneyIs('USD 437.59', $moneyBag->getValue(Currency::of('USD'), $currencyConverter));
    }
}
