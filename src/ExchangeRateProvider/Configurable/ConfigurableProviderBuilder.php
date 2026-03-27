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
     * @internal Use ConfigurableProvider::builder() instead, which is the canonical way to create a builder.
     *
     * @pure
     */
    public function __construct()
    {
    }

    /**
     * Adds an exchange rate for a currency pair.
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
     * @throws InvalidArgumentException If the exchange rate is not strictly positive, if the source and target
     *                                  currencies are the same and the rate is not equal to 1, or if a rate has
     *                                  already been set for this currency pair.
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

        if ($sourceCurrencyCode === $targetCurrencyCode) {
            if ($exchangeRate->isEqualTo(1)) {
                // no need to add the rate: ConfigurableProvider will always return 1 for same currency pairs
                return $this;
            }

            throw InvalidArgumentException::sameCurrencyRateNotOne();
        }

        if (isset($this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode])) {
            throw InvalidArgumentException::duplicateExchangeRate($sourceCurrencyCode, $targetCurrencyCode);
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
     * @internal
     *
     * @return array<string, array<string, BigNumber>>
     *
     * @pure
     */
    public function getExchangeRates(): array
    {
        return $this->exchangeRates;
    }
}
