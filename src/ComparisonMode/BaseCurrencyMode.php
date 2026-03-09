<?php

declare(strict_types=1);

namespace Brick\Money\ComparisonMode;

use Brick\Money\ComparisonMode;
use Brick\Money\Currency;
use Brick\Money\CurrencyConverter;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Monetary;
use Override;

/**
 * Compares two monies by converting both to a common base currency, then comparing.
 *
 * If A ≤ B and B ≤ C, then A ≤ C is guaranteed, so min()/max() results do not depend on argument order.
 * Use this mode when you need consistent ordering across more than two currencies, e.g. when sorting a list.
 *
 * Money, RationalMoney, and MoneyBag are all supported.
 */
final readonly class BaseCurrencyMode implements ComparisonMode
{
    private Currency $baseCurrency;

    /**
     * @throws UnknownCurrencyException If an unknown currency code is given.
     */
    public function __construct(Currency|string $baseCurrency)
    {
        $this->baseCurrency = $baseCurrency instanceof Currency ? $baseCurrency : Currency::of($baseCurrency);
    }

    #[Override]
    public function compare(Monetary $a, Monetary $b, CurrencyConverter $converter, array $dimensions): int
    {
        $rationalA = $converter->convertToRational($a, $this->baseCurrency, $dimensions);
        $rationalB = $converter->convertToRational($b, $this->baseCurrency, $dimensions);

        return $rationalA->compareTo($rationalB);
    }
}
