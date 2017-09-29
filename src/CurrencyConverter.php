<?php

namespace Brick\Money;

use Brick\Money\Exception\CurrencyConversionException;

use Brick\Math\BigRational;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;

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
     * @var Context
     */
    private $context;

    /**
     * @var int
     */
    private $roundingMode;

    /**
     * @param ExchangeRateProvider $exchangeRateProvider The exchange rate provider.
     * @param Context              $context              The context of the monies created by this currency converter.
     * @param int                  $roundingMode         The rounding mode, if necessary.
     */
    public function __construct(ExchangeRateProvider $exchangeRateProvider, Context $context, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $this->exchangeRateProvider = $exchangeRateProvider;
        $this->context              = $context;
        $this->roundingMode         = (int) $roundingMode;
    }

    /**
     * Converts the given money to the given currency.
     *
     * @param MoneyContainer  $moneyContainer The Money, RationalMoney or MoneyBag to convert.
     * @param Currency|string $currency       The currency, as a Currency instance or ISO currency code.
     *
     * @return Money
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     * @throws RoundingNecessaryException  If rounding is necessary but this converter uses RoundingMode::UNNECESSARY.
     */
    public function convert(MoneyContainer $moneyContainer, $currency)
    {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        $currencyCode = $currency->getCurrencyCode();

        $total = BigRational::zero();

        foreach ($moneyContainer->getAmounts() as $sourceCurrencyCode => $amount) {
            if ($sourceCurrencyCode !== $currencyCode) {
                $exchangeRate = $this->exchangeRateProvider->getExchangeRate($sourceCurrencyCode, $currencyCode);
                $amount = $amount->toBigRational()->multipliedBy($exchangeRate);
            }

            $total = $total->plus($amount);
        }

        return Money::create($total, $currency, $this->context, $this->roundingMode);
    }
}
