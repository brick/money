<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Money\Currency;

/**
 * Builder for ConfigurableProvider.
 *
 * String currency arguments are used directly as currency-code keys and are not resolved via Currency::of().
 * This intentionally supports both ISO and application-defined currency codes.
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
     * @param Currency|string      $sourceCurrency The source currency or currency code.
     * @param Currency|string      $targetCurrency The target currency or currency code.
     * @param BigNumber|int|string $exchangeRate   The exchange rate.
     *
     * @return $this This builder, for chaining.
     *
     * @throws MathException If the exchange rate is not a valid number.
     */
    public function addExchangeRate(
        Currency|string $sourceCurrency,
        Currency|string $targetCurrency,
        BigNumber|int|string $exchangeRate,
    ): self {
        $sourceCurrencyCode = $sourceCurrency instanceof Currency ? $sourceCurrency->getCurrencyCode() : $sourceCurrency;
        $targetCurrencyCode = $targetCurrency instanceof Currency ? $targetCurrency->getCurrencyCode() : $targetCurrency;

        $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode] = BigNumber::of($exchangeRate);

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
