<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigRational;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Exception\CurrencyConversionException;

/**
 * Converts monies into different currencies, using an exchange rate provider.
 */
final readonly class CurrencyConverter
{
    /**
     * @param ExchangeRateProvider $exchangeRateProvider The exchange rate provider.
     */
    public function __construct(
        private ExchangeRateProvider $exchangeRateProvider,
    ) {
    }

    /**
     * Converts the given money to the given currency.
     *
     * @param Monetary            $money        The Money, RationalMoney or MoneyBag to convert.
     * @param Currency|string|int $currency     The Currency instance, ISO currency code or ISO numeric currency code.
     * @param Context|null        $context      A context to create the money in, or null to use the default.
     * @param RoundingMode        $roundingMode The rounding mode, if necessary.
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     * @throws RoundingNecessaryException  If rounding is necessary and RoundingMode::UNNECESSARY is used.
     */
    public function convert(
        Monetary $money,
        Currency|string|int $currency,
        ?Context $context = null,
        RoundingMode $roundingMode = RoundingMode::UNNECESSARY,
    ): Money {
        return $this
            ->convertToRational($money, $currency)
            ->to($context ?? new DefaultContext(), $roundingMode);
    }

    /**
     * Converts the given money to the given currency, and returns the result as a RationalMoney with no rounding.
     *
     * @param Monetary            $money    The Money, RationalMoney or MoneyBag to convert.
     * @param Currency|string|int $currency The Currency instance, ISO currency code or ISO numeric currency code.
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     */
    public function convertToRational(Monetary $money, Currency|string|int $currency): RationalMoney
    {
        if (! $currency instanceof Currency) {
            $currency = Currency::of($currency);
        }

        $currencyCode = $currency->getCurrencyCode();

        $total = BigRational::zero();

        foreach ($money->getMonies() as $containedMoney) {
            $sourceCurrency = $containedMoney->getCurrency();
            $sourceCurrencyCode = $sourceCurrency->getCurrencyCode();

            $amount = $containedMoney->getAmount();

            if ($sourceCurrencyCode !== $currencyCode) {
                $exchangeRate = $this->exchangeRateProvider->getExchangeRate($sourceCurrencyCode, $currencyCode);
                $amount = $amount->toBigRational()->multipliedBy($exchangeRate);
            }

            $total = $total->plus($amount);
        }

        return new RationalMoney($total, $currency);
    }
}
