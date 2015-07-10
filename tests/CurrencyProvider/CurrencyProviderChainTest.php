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
    /**
     * @return CurrencyProviderChain
     */
    private function getCurrencyProviderChain()
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
        $provider = $this->getCurrencyProviderChain();

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
        $providerChain = $this->getCurrencyProviderChain();

        // Add a different GBP instance, that comes after the first one.
        // The first instance available in the chain takes precedence.
        $provider = new ConfigurableCurrencyProvider();
        $provider->addCurrency(Currency::create('GBP', 999, 'A competing GBP instance', 6));
        $providerChain->addCurrencyProvider($provider);

        $this->assertCurrencyProviderContains([
            'EUR' => ISOCurrencyProvider::getInstance()->getCurrency('EUR'),
            'GBP' => ISOCurrencyProvider::getInstance()->getCurrency('GBP'),
            'USD' => ISOCurrencyProvider::getInstance()->getCurrency('USD'),
            'CAD' => ISOCurrencyProvider::getInstance()->getCurrency('CAD'),
        ], $providerChain);
    }
}
