<?php

namespace Brick\Money;

use Brick\Money\ExchangeRateProvider;
use Brick\Money\Exception\CurrencyConversionException;

use Brick\Math\Exception\RoundingNecessaryException;

/**
 * Converts monies into different currencies, using an exchange rate provider.
 */
class CurrencyConverter
{
    /**
     * @var ExchangeRateProvider
     */
    private $exchangeRateProvider;

    /**
     * @var MoneyContext
     */
    private $context;

    /**
     * @param ExchangeRateProvider $exchangeRateProvider The exchange rate provider.
     * @param MoneyContext         $context              The scale & rounding context to use.
     */
    public function __construct(ExchangeRateProvider $exchangeRateProvider, MoneyContext $context)
    {
        $this->exchangeRateProvider = $exchangeRateProvider;
        $this->context              = $context;
    }

    /**
     * @param Money    $money
     * @param Currency $currency
     *
     * @return Money
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     * @throws RoundingNecessaryException  If rounding was necessary but this converter uses RoundingMode::UNNECESSARY.
     */
    public function convert(Money $money, Currency $currency)
    {
        if ($money->getCurrency()->is($currency)) {
            $exchangeRate = 1;
        } else {
            $exchangeRate = $this->exchangeRateProvider->getExchangeRate($money->getCurrency()->getCurrencyCode(), $currency->getCurrencyCode());
        }

        return $money->convertedTo($currency, $exchangeRate, $this->context);
    }
}
