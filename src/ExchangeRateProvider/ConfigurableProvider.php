<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider;

/**
 * A configurable exchange rate provider.
 */
final class ConfigurableProvider implements ExchangeRateProvider
{
    /**
     * @var array<string, array<string, BigNumber|int|float|string>>
     */
    private array $exchangeRates = [];

    /**
     * @return ConfigurableProvider This instance, for chaining.
     */
    public function setExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, BigNumber|int|float|string $exchangeRate): self
    {
        $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode] = $exchangeRate;

        return $this;
    }

    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode): BigNumber|int|float|string
    {
        if (isset($this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode])) {
            return $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode];
        }

        throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode);
    }
}
