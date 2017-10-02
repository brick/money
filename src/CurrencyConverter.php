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
     *
     * @var ExchangeRateProvider
     */
    private $exchangeRateProvider;

    /**
     * The context of the monies created by this currency converter.
     *
     * @var Context
     */
    private $context;

    /**
     * @param ExchangeRateProvider $exchangeRateProvider The exchange rate provider.
     * @param Context|null         $context              A context to create the monies in, or null to use the default.
     */
    public function __construct(ExchangeRateProvider $exchangeRateProvider, Context $context = null)
    {
        if ($context === null) {
            $context = new DefaultContext();
        }

        $this->exchangeRateProvider = $exchangeRateProvider;
        $this->context              = $context;
    }

    /**
     * Converts the given money to the given currency.
     *
     * @param MoneyContainer      $moneyContainer The Money, RationalMoney or MoneyBag to convert.
     * @param Currency|string|int $currency       The Currency instance, ISO currency code or ISO numeric currency code.
     * @param int                 $roundingMode   The rounding mode, if necessary.
     *
     * @return Money
     *
     * @throws CurrencyConversionException If the exchange rate is not available.
     * @throws RoundingNecessaryException  If rounding is necessary and RoundingMode::UNNECESSARY is used.
     */
    public function convert(MoneyContainer $moneyContainer, $currency, int $roundingMode = RoundingMode::UNNECESSARY) : Money
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

        return Money::create($total, $currency, $this->context, $roundingMode);
    }
}
