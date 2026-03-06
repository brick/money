<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigNumber;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Money\Currency;
use Brick\Money\Exception\ExchangeRateProviderException;
use Brick\Money\ExchangeRateProvider;
use Override;

/**
 * Calculates exchange rates relative to a base currency.
 *
 * This provider is useful when your exchange rates source only provides exchange rates relative to a single currency.
 *
 * For example, if your source only has exchange rates from USD to EUR and USD to GBP,
 * using this provider on top of it would allow you to get an exchange rate from EUR to USD, GBP to USD,
 * or even EUR to GBP and GBP to EUR.
 */
final readonly class BaseCurrencyProvider implements ExchangeRateProvider
{
    private Currency $baseCurrency;

    /**
     * @param ExchangeRateProvider $provider     The provider for rates relative to the base currency.
     * @param Currency|string      $baseCurrency The currency or currency code all the exchanges rates are based on.
     */
    public function __construct(
        private ExchangeRateProvider $provider,
        Currency|string $baseCurrency,
    ) {
        $this->baseCurrency = $baseCurrency instanceof Currency ? $baseCurrency : Currency::of($baseCurrency);
    }

    #[Override]
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
    {
        $baseCurrencyCode = $this->baseCurrency->getCurrencyCode();

        if ($sourceCurrency->getCurrencyCode() === $baseCurrencyCode) {
            return $this->provider->getExchangeRate($sourceCurrency, $targetCurrency, $dimensions);
        }

        if ($targetCurrency->getCurrencyCode() === $baseCurrencyCode) {
            $exchangeRate = $this->provider->getExchangeRate($targetCurrency, $sourceCurrency, $dimensions);

            if ($exchangeRate === null) {
                return null;
            }

            try {
                return $exchangeRate->toBigRational()->reciprocal();
            } catch (DivisionByZeroException $e) {
                throw new ExchangeRateProviderException('Failed to derive exchange rate from base-currency rates: encountered a zero rate.', $e);
            }
        }

        $baseToSource = $this->provider->getExchangeRate($this->baseCurrency, $sourceCurrency, $dimensions);

        if ($baseToSource === null) {
            return null;
        }

        $baseToTarget = $this->provider->getExchangeRate($this->baseCurrency, $targetCurrency, $dimensions);

        if ($baseToTarget === null) {
            return null;
        }

        try {
            return $baseToTarget->toBigRational()->dividedBy($baseToSource);
        } catch (DivisionByZeroException $e) {
            throw new ExchangeRateProviderException('Failed to derive exchange rate from base-currency rates: encountered a zero rate.', $e);
        }
    }
}
