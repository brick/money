<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Money\Context\DefaultContext;
use Brick\Money\Exception\CurrencyConversionException;

use Brick\Math\BigRational;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;

/**
 * Converts monies into different currencies, using an exchange rate provider.
 */
final class CurrencyConverter
{
    /**
     * The exchange rate provider.
     */
    private ExchangeRateProvider $exchangeRateProvider;

    /**
     * @param ExchangeRateProvider $exchangeRateProvider The exchange rate provider.
     */
    public function __construct(ExchangeRateProvider $exchangeRateProvider)
    {
        $this->exchangeRateProvider = $exchangeRateProvider;
    }

    /**
     * Converts the given money to the given currency.
     *
     * @psalm-param RoundingMode::* $roundingMode
     *
     * @param MoneyContainer      $moneyContainer The Money, RationalMoney or MoneyBag to convert.
     * @param Currency|string|int $currency       The Currency instance, ISO currency code or ISO numeric currency code.
     * @param Context|null        $context        A context to create the money in, or null to use the default.
     * @param int                 $roundingMode   The rounding mode, if necessary.
     *
     * @return Money
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     * @throws RoundingNecessaryException  If rounding is necessary and RoundingMode::UNNECESSARY is used.
     */
    public function convert(
        MoneyContainer $moneyContainer,
        Currency|string|int $currency,
        ?Context $context = null,
        int $roundingMode = RoundingMode::UNNECESSARY,
    ) : Money {
        return $this
            ->convertToRational($moneyContainer, $currency)
            ->to($context ?? new DefaultContext(), $roundingMode);
    }

    /**
     * Converts the given money to the given currency, and returns the result as a RationalMoney with no rounding.
     *
     * @param MoneyContainer      $moneyContainer The Money, RationalMoney or MoneyBag to convert.
     * @param Currency|string|int $currency       The Currency instance, ISO currency code or ISO numeric currency code.
     *
     * @return RationalMoney
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     */
    public function convertToRational(MoneyContainer $moneyContainer, Currency|string|int $currency) : RationalMoney
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

        return new RationalMoney($total, $currency);
    }
}
