<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Exception\CurrencyConversionException;
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

    #[Override]
    public function getExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode): string
    {
        $this->calls++;

        if (isset($this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode])) {
            return $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode];
        }

        throw CurrencyConversionException::exchangeRateNotAvailable($sourceCurrencyCode, $targetCurrencyCode);
    }
}
