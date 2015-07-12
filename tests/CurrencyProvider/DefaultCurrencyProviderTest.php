<?php

namespace Brick\Money\Tests\CurrencyProvider;

use Brick\Money\Currency;
use Brick\Money\CurrencyProvider\DefaultCurrencyProvider;
use Brick\Money\CurrencyProvider\ISOCurrencyProvider;
use Brick\Money\Tests\AbstractTestCase;

/**
 * Tests for class DefaultCurrencyProvider.
 */
class DefaultCurrencyProviderTest extends AbstractTestCase
{
    public function testGetInstance()
    {
        $this->assertInstanceOf(DefaultCurrencyProvider::class, DefaultCurrencyProvider::getInstance());
        $this->assertSame(DefaultCurrencyProvider::getInstance(), DefaultCurrencyProvider::getInstance());
    }

    public function testReset()
    {
        $previousInstance = DefaultCurrencyProvider::getInstance();
        DefaultCurrencyProvider::reset();

        $this->assertInstanceOf(DefaultCurrencyProvider::class, DefaultCurrencyProvider::getInstance());
        $this->assertSame(DefaultCurrencyProvider::getInstance(), DefaultCurrencyProvider::getInstance());
        $this->assertNotSame($previousInstance, DefaultCurrencyProvider::getInstance());
    }

    /**
     * @depends testGetInstance
     */
    public function testDefaultProviderContainsISOCurrencies()
    {
        $isoCurrencies = ISOCurrencyProvider::getInstance()->getAvailableCurrencies();
        $this->assertCurrencyProviderContains($isoCurrencies, DefaultCurrencyProvider::getInstance());
    }

    /**
     * @depends testDefaultProviderContainsISOCurrencies
     */
    public function testAddCustomCurrency()
    {
        $bitCoin = Currency::create('BTC', 0, 'BitCoin', 8);
        $returnValue = DefaultCurrencyProvider::getInstance()->addCurrency($bitCoin);

        $this->assertSame(DefaultCurrencyProvider::getInstance(), $returnValue);

        $expectedCurrencies = ISOCurrencyProvider::getInstance()->getAvailableCurrencies();
        $expectedCurrencies['BTC'] = $bitCoin;

        $this->assertCurrencyProviderContains($expectedCurrencies, DefaultCurrencyProvider::getInstance());
    }

    /**
     * @depends testAddCustomCurrency
     */
    public function testRemoveCustomCurrency()
    {
        $bitCoin = DefaultCurrencyProvider::getInstance()->getCurrency('BTC');
        $returnValue = DefaultCurrencyProvider::getInstance()->removeCurrency($bitCoin);

        $this->assertSame(DefaultCurrencyProvider::getInstance(), $returnValue);

        $isoCurrencies = ISOCurrencyProvider::getInstance()->getAvailableCurrencies();
        $this->assertCurrencyProviderContains($isoCurrencies, DefaultCurrencyProvider::getInstance());
    }

    /**
     * @depends testRemoveCustomCurrency
     */
    public function testAttemptToOverrideISOCurrency()
    {
        $euro = Currency::create('EUR', 0, 'Another Euro', 6);
        DefaultCurrencyProvider::getInstance()->addCurrency($euro);

        $isoCurrencies = ISOCurrencyProvider::getInstance()->getAvailableCurrencies();
        $this->assertCurrencyProviderContains($isoCurrencies, DefaultCurrencyProvider::getInstance());
    }

    /**
     * @depends testAttemptToOverrideISOCurrency
     */
    public function testAttemptToRemoveISOCurrency()
    {
        $euro = ISOCurrencyProvider::getInstance()->getCurrency('EUR');
        DefaultCurrencyProvider::getInstance()->removeCurrency($euro);

        $isoCurrencies = ISOCurrencyProvider::getInstance()->getAvailableCurrencies();
        $this->assertCurrencyProviderContains($isoCurrencies, DefaultCurrencyProvider::getInstance());
    }
}
