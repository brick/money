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
     * @var Adjustment|null
     */
    private $adjustment;

    /**
     * @param ExchangeRateProvider $exchangeRateProvider The exchange rate provider.
     * @param Adjustment|null      $adjustment           An optional adjustment.
     */
    public function __construct(ExchangeRateProvider $exchangeRateProvider, Adjustment $adjustment = null)
    {
        $this->exchangeRateProvider = $exchangeRateProvider;
        $this->adjustment           = $adjustment;
    }

    /**
     * @param Money           $money
     * @param Currency|string $currency
     *
     * @return Money
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     * @throws RoundingNecessaryException  If rounding was necessary but this converter uses RoundingMode::UNNECESSARY.
     */
    public function convert(Money $money, $currency)
    {
        $currency = Currency::of($currency);

        if ($money->getCurrency()->is($currency)) {
            $exchangeRate = 1;
        } else {
            $exchangeRate = $this->exchangeRateProvider->getExchangeRate($money->getCurrency()->getCurrencyCode(), $currency->getCurrencyCode());
        }

        return $money->convertedTo($currency, $exchangeRate, $this->adjustment);
    }
}
