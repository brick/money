<?php

namespace Brick\Money;

use Brick\Money\ExchangeRateProvider;
use Brick\Money\Exception\CurrencyConversionException;

use Brick\Math\RoundingMode;
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
     * @var int
     */
    private $roundingMode = RoundingMode::FLOOR;

    /**
     * @param ExchangeRateProvider $exchangeRateProvider The exchange rate provider.
     * @param int                  $roundingMode         The rounding mode to use for conversions.
     */
    public function __construct(ExchangeRateProvider $exchangeRateProvider, $roundingMode = RoundingMode::DOWN)
    {
        $this->exchangeRateProvider = $exchangeRateProvider;
        $this->roundingMode         = $roundingMode;
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
            return $money;
        }

        $exchangeRate = $this->exchangeRateProvider->getExchangeRate($money->getCurrency(), $currency);

        return $money->convertedTo($currency, $exchangeRate, $this->roundingMode);
    }
}
