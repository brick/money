<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;
use Override;

/**
 * A mock implementation of ExchangeRateProvider for tests.
 */
class ProviderMock implements ExchangeRateProvider
{
    /**
     * @var array<string, array<string, string>>
     */
    private array $exchangeRates = [
        'EUR' => [
            'USD' => '1.1',
            'GBP' => '0.9',
        ],
    ];

    /**
     * The number of calls to getExchangeRate().
     */
    private int $calls = 0;

    public function getCalls(): int
    {
        return $this->calls;
    }

    public function setExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, string $exchangeRate): void
    {
        $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode] = $exchangeRate;
    }

    #[Override]
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
    {
        $this->calls++;

        $sourceCurrencyCode = $sourceCurrency->getCurrencyCode();
        $targetCurrencyCode = $targetCurrency->getCurrencyCode();

        if (isset($this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode])) {
            return BigNumber::of($this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode]);
        }

        return null;
    }
}
