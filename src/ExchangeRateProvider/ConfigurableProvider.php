<?php

declare(strict_types=1);

namespace Brick\Money\ExchangeRateProvider;

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Money\Currency;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\Configurable\ConfigurableProviderBuilder;
use Override;

/**
 * An immutable exchange rate provider configured with a fixed set of rates.
 *
 * Use ConfigurableProvider::builder() to create an instance.
 */
final readonly class ConfigurableProvider implements ExchangeRateProvider
{
    /**
     * Private constructor.
     *
     * To create an instance, use ConfigurableProvider::builder()->...->build().
     *
     * @param array<string, array<string, BigNumber>> $exchangeRates The exchange rates, indexed by source and target
     *                                                               currency code.
     *
     * @pure
     */
    private function __construct(
        private array $exchangeRates,
    ) {
    }

    /**
     * Returns a new builder for creating a ConfigurableProvider.
     *
     * @pure
     */
    public static function builder(): ConfigurableProviderBuilder
    {
        return new ConfigurableProviderBuilder();
    }

    /**
     * @internal
     *
     * @pure
     */
    public static function fromBuilder(ConfigurableProviderBuilder $builder): self
    {
        return new self($builder->getExchangeRates());
    }

    #[Override]
    public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
    {
        if ($sourceCurrency->isEqualTo($targetCurrency)) {
            return BigInteger::one();
        }

        if ($dimensions !== []) {
            // dimensions are not supported
            return null;
        }

        $sourceCurrencyCode = $sourceCurrency->getCurrencyCode();
        $targetCurrencyCode = $targetCurrency->getCurrencyCode();

        return $this->exchangeRates[$sourceCurrencyCode][$targetCurrencyCode] ?? null;
    }
}
