<?php

namespace Brick\Money;

use Brick\Math\RoundingMode;
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
     * @var Context|null
     */
    private $context;

    /**
     * @var int
     */
    private $roundingMode;

    /**
     * @param ExchangeRateProvider $exchangeRateProvider The exchange rate provider.
     * @param Context|null         $context              An optional context. If not provided, the Money's context is used.
     * @param int                  $roundingMode         An optional rounding mode.
     */
    public function __construct(ExchangeRateProvider $exchangeRateProvider, Context $context = null, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $this->exchangeRateProvider = $exchangeRateProvider;
        $this->context              = $context;
        $this->roundingMode         = (int) $roundingMode;
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

        return $money->convertedTo($currency, $exchangeRate, $this->context, $this->roundingMode);
    }
}
