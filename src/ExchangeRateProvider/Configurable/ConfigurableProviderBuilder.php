<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider\Configurable;

use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Money\Currency;
use Brick\Money\Exception\InvalidArgumentException;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;

/**
 * Builder for ConfigurableProvider.
 */
final class ConfigurableProviderBuilder
{
    /**
     * @var array<string, array<string, BigNumber>>
     */
    private array $exchangeRates = [];

    /**
     * Adds an exchange rate for a currency pair.
     *
     * If an exchange rate is added more than once for the same currency pair, the later value replaces the previous
     * one.
     *
     * Both ISO and non-ISO currency codes are accepted.
     *
     * @param Currency|string      $sourceCurrency The source currency or currency code.
     * @param Currency|string      $targetCurrency The target currency or currency code.
     * @param BigNumber|int|string $exchangeRate   The exchange rate.
     *
     * @return $this This builder, for chaining.
     *
     * @throws MathException            If the exchange rate is not a valid number.
     * @throws InvalidArgumentException If the exchange rate is not strictly positive.
     */
    public function addExchangeRate(
        Currency|string $sourceCurrency,
        Currency|string $targetCurrency,
        BigNumber|int|string $exchangeRate,
    ): self {
        $sourceCurrencyCode = $sourceCurrency instanceof Currency ? $sourceCurrency->getCurrencyCode() : $sourceCurrency;
        $targetCurrencyCode = $targetCurrency instanceof Currency ? $targetCurrency->getCurrencyCode() : $targetCurrency;
        $exchangeRate = BigNumber::of($exchangeRate);

        if ($exchangeRate->isNegativeOrZero()) {
            throw InvalidArgumentException::nonPositiveExchangeRate();
        }

        $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode] = $exchangeRate;

        return $this;
    }

    /**
     * Builds an immutable ConfigurableProvider from the configured exchange rates.
     */
    public function build(): ConfigurableProvider
    {
        return ConfigurableProvider::fromBuilder($this);
    }

    /**
     * @return array<string, array<string, BigNumber>>
     *
     * @pure
     */
    public function getExchangeRates(): array
    {
        return $this->exchangeRates;
    }
}
