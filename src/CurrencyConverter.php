<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigRational;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Exception\ContextException;
use Brick\Money\Exception\ExchangeRateNotFoundException;
use Brick\Money\Exception\ExchangeRateProviderException;
use Brick\Money\Exception\UnknownCurrencyException;

/**
 * Converts monies into different currencies, using an exchange rate provider.
 */
final readonly class CurrencyConverter
{
    public function __construct(
        private ExchangeRateProvider $exchangeRateProvider,
    ) {
    }

    /**
     * Converts the given money to the given currency.
     *
     * @param Monetary             $money        The Money, RationalMoney, or MoneyBag to convert.
     * @param Currency|string      $currency     The Currency instance or ISO currency code.
     * @param array<string, mixed> $dimensions   Additional exchange-rate lookup dimensions (e.g., date or rate type).
     * @param Context              $context      A context to create the money in, defaults to DefaultContext.
     * @param RoundingMode         $roundingMode The rounding mode, if necessary.
     *
     * @throws UnknownCurrencyException      If an unknown currency code is given.
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     * @throws RoundingNecessaryException    If rounding is necessary and RoundingMode::Unnecessary is used.
     * @throws ContextException              If the context does not apply.
     */
    public function convert(
        Monetary $money,
        Currency|string $currency,
        array $dimensions = [],
        Context $context = new DefaultContext(),
        RoundingMode $roundingMode = RoundingMode::Unnecessary,
    ): Money {
        return $this
            ->convertToRational($money, $currency, $dimensions)
            ->toContext($context, $roundingMode);
    }

    /**
     * Converts the given money to the given currency, and returns the result as a RationalMoney with no rounding.
     *
     * @param Monetary             $money      The Money, RationalMoney, or MoneyBag to convert.
     * @param Currency|string      $currency   The Currency instance or ISO currency code.
     * @param array<string, mixed> $dimensions Additional exchange-rate lookup dimensions (e.g., date or rate type).
     *
     * @throws UnknownCurrencyException      If an unknown currency code is given.
     * @throws ExchangeRateNotFoundException If the exchange rate is not available.
     * @throws ExchangeRateProviderException If an error occurs while retrieving exchange rates.
     */
    public function convertToRational(
        Monetary $money,
        Currency|string $currency,
        array $dimensions = [],
    ): RationalMoney {
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
                $exchangeRate = $this->exchangeRateProvider->getExchangeRate($sourceCurrency, $currency, $dimensions);

                if ($exchangeRate === null) {
                    throw ExchangeRateNotFoundException::exchangeRateNotFound($sourceCurrency, $currency);
                }

                $amount = $amount->toBigRational()->multipliedBy($exchangeRate);
            }

            $total = $total->plus($amount);
        }

        return new RationalMoney($total, $currency);
    }
}
