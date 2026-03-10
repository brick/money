<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;
use Override;

/**
 * A chain of exchange rate providers.
 */
final readonly class ChainProvider implements ExchangeRateProvider
{
    /**
     * @var ExchangeRateProvider[]
     */
    private array $providers;

    public function __construct(ExchangeRateProvider ...$providers)
    {
        $this->providers = $providers;
    }

    #[Override]
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
    {
        foreach ($this->providers as $provider) {
            $exchangeRate = $provider->getExchangeRate($sourceCurrency, $targetCurrency, $dimensions);

            if ($exchangeRate !== null) {
                return $exchangeRate;
            }
        }

        return null;
    }
}
