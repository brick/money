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
     * Converts the given Money to the given Currency.
     *
     * @param Money           $money    The money to convert.
     * @param Currency|string $currency The target currency, as a Currency instance or ISO currency code.
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

    /**
     * Returns the total value of the given MoneyBag, in the given Currency.
     *
     * @param MoneyBag        $moneyBag The money bag.
     * @param Currency|string $currency The currency, as a Currency instance or ISO currency code.
     *
     * @return Money
     */
    public function getTotal(MoneyBag $moneyBag, $currency)
    {
        $targetCurrency = Currency::of($currency);
        $targetCurrencyCode = $targetCurrency->getCurrencyCode();

        $total = BigRational::zero();

        foreach ($moneyBag->getAmounts() as $currencyCode => $amount) {
            $exchangeRate = $this->exchangeRateProvider->getExchangeRate($currencyCode, $targetCurrencyCode);
            $convertedAmount = $amount->toBigRational()->multipliedBy($exchangeRate);
            $total = $total->plus($convertedAmount);
        }

        return Money::create($total, $targetCurrency, $this->context, $this->roundingMode);
    }
}
