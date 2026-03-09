<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;
use Override;

/**
 * A configurable exchange rate provider.
 */
final class ConfigurableProvider implements ExchangeRateProvider
{
    /**
     * The configured exchange rates, indexed by source currency code and target currency code.
     *
     * @var array<string, array<string, BigNumber>>
     */
    private array $exchangeRates = [];

    /**
     * Sets an exchange rate for a currency pair.
     *
     * This method allows non-ISO currency codes.
     *
     * @return ConfigurableProvider This instance, for chaining.
     *
     * @throws MathException If the exchange rate is not a valid number.
     */
    public function setExchangeRate(
        Currency|string $sourceCurrency,
        Currency|string $targetCurrency,
        BigNumber|int|string $exchangeRate,
    ): self {
        $sourceCurrencyCode = $sourceCurrency instanceof Currency ? $sourceCurrency->getCurrencyCode() : $sourceCurrency;
        $targetCurrencyCode = $targetCurrency instanceof Currency ? $targetCurrency->getCurrencyCode() : $targetCurrency;

        $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode] = BigNumber::of($exchangeRate);

        return $this;
    }

    #[Override]
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
    {
        if ($dimensions !== []) {
            // dimensions are not supported
            return null;
        }

        $sourceCurrencyCode = $sourceCurrency->getCurrencyCode();
        $targetCurrencyCode = $targetCurrency->getCurrencyCode();

        return $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode] ?? null;
    }
}
