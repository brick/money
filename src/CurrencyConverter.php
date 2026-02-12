<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigRational;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Exception\CurrencyConversionException;

use function trigger_error;

use const E_USER_DEPRECATED;

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
     * @param Monetary        $money        The Money, RationalMoney or MoneyBag to convert.
     * @param Currency|string $currency     The Currency instance or ISO currency code.
     * @param Context|null    $context      A context to create the money in, defaults to DefaultContext.
     * @param RoundingMode    $roundingMode The rounding mode, if necessary.
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     * @throws RoundingNecessaryException  If rounding is necessary and RoundingMode::Unnecessary is used.
     */
    public function convert(
        Monetary $money,
        Currency|string $currency,
        ?Context $context = new DefaultContext(),
        RoundingMode $roundingMode = RoundingMode::Unnecessary,
    ): Money {
        if ($context === null) {
            trigger_error(
                'Passing null for the $context parameter to CurrencyConverter::convert() is deprecated, use named arguments to skip to rounding mode.',
                E_USER_DEPRECATED,
            );

            $context = new DefaultContext();
        }

        return $this
            ->convertToRational($money, $currency)
            ->toContext($context, $roundingMode);
    }

    /**
     * Converts the given money to the given currency, and returns the result as a RationalMoney with no rounding.
     *
     * @param Monetary        $money    The Money, RationalMoney or MoneyBag to convert.
     * @param Currency|string $currency The Currency instance or ISO currency code.
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     */
    public function convertToRational(Monetary $money, Currency|string $currency): RationalMoney
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
