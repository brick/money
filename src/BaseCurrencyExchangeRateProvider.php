<?php

namespace Brick\Money;

use Brick\Math\BigRational;

/**
 * Calculates exchange rates relative to a base currency.
 *
 * This provider is useful when your exchange rates source only provides exchange rates relative to a single currency.
 *
 * For example, if your source only has exchange rates from USD to EUR and USD to GBP,
 * using this provider on top of it would allow you to get an exchange rate from EUR to USD, GBP to USD,
 * or even EUR to GBP and GBP to EUR.
 */
class BaseCurrencyExchangeRateProvider implements ExchangeRateProvider
{
    /**
     * The provider for rates relative to the base currency.
     *
     * @var ExchangeRateProvider
     */
    private $provider;

    /**
     * The currency all the exchanges rates are based on.
     *
     * @var Currency
     */
    private $baseCurrency;

    /**
     * @param ExchangeRateProvider $provider     The provider for rates relative to the base currency.
     * @param Currency             $baseCurrency The currency all the exchanges rates are based on.
     */
    public function __construct(ExchangeRateProvider $provider, Currency $baseCurrency)
    {
        $this->provider     = $provider;
        $this->baseCurrency = $baseCurrency;
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(Currency $source, Currency $target)
    {
        if ($source->is($this->baseCurrency)) {
            return $this->provider->getExchangeRate($source, $target);
        }

        if ($target->is($this->baseCurrency)) {
            return BigRational::of($this->provider->getExchangeRate($target, $source))->reciprocal();
        }

        $baseToSource = $this->provider->getExchangeRate($this->baseCurrency, $source);
        $baseToTarget = $this->provider->getExchangeRate($this->baseCurrency, $target);

        return BigRational::of($baseToTarget)->dividedBy($baseToSource);
    }
}
