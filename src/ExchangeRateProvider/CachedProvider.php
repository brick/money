<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;
use Override;

/**
 * Caches the results of another exchange rate provider.
 */
final class CachedProvider implements ExchangeRateProvider
{
    /**
     * The cached exchange rates, indexed by source currency code and target currency code.
     *
     * @var array<string, array<string, BigNumber>>
     */
    private array $exchangeRates = [];

    /**
     * @param ExchangeRateProvider $provider The underlying exchange rate provider.
     */
    public function __construct(
        private readonly ExchangeRateProvider $provider,
    ) {
    }

    #[Override]
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency): ?BigNumber
    {
        $sourceCurrencyCode = $sourceCurrency->getCurrencyCode();
        $targetCurrencyCode = $targetCurrency->getCurrencyCode();

        if (isset($this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode])) {
            return $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode];
        }

        $exchangeRate = $this->provider->getExchangeRate($sourceCurrency, $targetCurrency);

        if ($exchangeRate === null) {
            return null;
        }

        $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode] = $exchangeRate;

        return $exchangeRate;
    }

    /**
     * Invalidates the cache.
     *
     * This forces the exchange rates to be fetched again from the underlying provider.
     */
    public function invalidate(): void
    {
        $this->exchangeRates = [];
    }
}
