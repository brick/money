<?php

namespace Brick\Money\Tests\CurrencyProvider;

use Brick\Money\Currency;
use Brick\Money\CurrencyProvider\ConfigurableCurrencyProvider;
use Brick\Money\CurrencyProvider\CurrencyProviderChain;
use Brick\Money\CurrencyProvider\ISOCurrencyProvider;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Tests\AbstractTestCase;

/**
 * Tests for class CurrencyProviderChain.
 */
class CurrencyProviderChainTest extends AbstractTestCase
{
    public function testAddRemoveCurrencyProvider()
    {
        $bitCoin = Currency::create('BTC', 0, 'BitCoin', 8);

        $configurableProvider = new ConfigurableCurrencyProvider();
        $configurableProvider->addCurrency($bitCoin);

        $providerChain = new CurrencyProviderChain();
        $providerChain->addCurrencyProvider($configurableProvider);

        $this->assertCurrencyProviderContains([
            'BTC' => $bitCoin
        ], $providerChain);

        $providerChain->removeCurrencyProvider($configurableProvider);

        $this->assertCurrencyProviderContains([], $providerChain);
    }

    /**
     * @return CurrencyProviderChain
     */
    private function createCurrencyProviderChain()
    {
        $providerChain = new CurrencyProviderChain();

        $isoProvider = ISOCurrencyProvider::getInstance();

        $provider = new ConfigurableCurrencyProvider();
        $provider->addCurrency($isoProvider->getCurrency('EUR'));
        $provider->addCurrency($isoProvider->getCurrency('GBP'));
        $providerChain->addCurrencyProvider($provider);

        $provider = new ConfigurableCurrencyProvider();
        $provider->addCurrency($isoProvider->getCurrency('USD'));
        $provider->addCurrency($isoProvider->getCurrency('CAD'));
        $providerChain->addCurrencyProvider($provider);

        return $providerChain;
    }

    /**
     * @dataProvider providerGetCurrency
     *
     * @param string $currencyCode
     * @param bool   $expectsException
     */
    public function testGetCurrency($currencyCode, $expectsException)
    {
        $provider = $this->createCurrencyProviderChain();

        if ($expectsException) {
            $this->setExpectedException(UnknownCurrencyException::class);
        }

        $actualCurrency = $provider->getCurrency($currencyCode);

        if (! $expectsException) {
            $expectedCurrency = ISOCurrencyProvider::getInstance()->getCurrency($currencyCode);
            $this->assertSame($expectedCurrency, $actualCurrency);
        }
    }

    /**
     * @return array
     */
    public function providerGetCurrency()
    {
        return [
            ['EUR', false],
            ['GBP', false],
            ['USD', false],
            ['CAD', false],
            ['AUD', true],
            ['NZD', true],
            ['JPY', true],
        ];
    }

    public function testGetAvailableCurrencies()
    {
        $providerChain = $this->createCurrencyProviderChain();

        // Add a different GBP instance, that comes after the first one.
        // The first instance available in the chain takes precedence.
        $provider = new ConfigurableCurrencyProvider();
        $provider->addCurrency(Currency::create('GBP', 999, 'A competing GBP instance', 6));
        $providerChain->addCurrencyProvider($provider);

        $isoCurrencyProvider = ISOCurrencyProvider::getInstance();

        $this->assertCurrencyProviderContains([
            'EUR' => $isoCurrencyProvider->getCurrency('EUR'),
            'GBP' => $isoCurrencyProvider->getCurrency('GBP'),
            'USD' => $isoCurrencyProvider->getCurrency('USD'),
            'CAD' => $isoCurrencyProvider->getCurrency('CAD'),
        ], $providerChain);
    }
}
