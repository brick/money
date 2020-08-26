<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigNumber;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Exception\UnknownCurrencyException;

/**
 * A factory for monies.
 *
 * MoneyFactory is an alternative to Money::of() and Money::ofMinor(), to cover the following use cases:
 *
 * - support for instantiating monies with custom currencies from their currency code
 * - support for custom default context
 * - support for custom default rounding mode
 */
final class MoneyFactory
{
    /**
     * @var CurrencyProvider
     */
    private $currencyProvider;

    /**
     * @var Context
     */
    private $defaultContext;

    /**
     * @var int
     */
    private $defaultRoundingMode;

    /**
     * @param CurrencyProvider|null $currencyProvider    The currency provider. Defaults to ISOCurrencyProvider.
     * @param Context|null          $defaultContext      The default context for monies created with this factory.
     *                                                   Defaults to DefaultContext.
     * @param int                   $defaultRoundingMode The default rounding mode for monies created with this factory.
     *                                                   Note that the rounding mode only applies when creating a Money.
     *                                                   Defaults to RoundingMode::UNNECESSARY.
     */
    public function __construct(?CurrencyProvider $currencyProvider, ?Context $defaultContext = null, int $defaultRoundingMode = RoundingMode::UNNECESSARY)
    {
        $this->currencyProvider    = $currencyProvider ?? ISOCurrencyProvider::getInstance();
        $this->defaultContext      = $defaultContext ?? new DefaultContext();
        $this->defaultRoundingMode = $defaultRoundingMode;
    }

    /**
     * Returns a Money of the given amount and currency.
     *
     * @param BigNumber|int|float|string $amount       The monetary amount.
     * @param Currency|string|int        $currency     The Currency instance, currency code or numeric currency code.
     * @param Context|null               $context      An optional Context.
     *                                                 If not provided, defaults to the factory's default context.
     * @param int|null                   $roundingMode An optional RoundingMode, if the amount does not fit the context.
     *                                                 If not provided, defaults to the factory's default rounding mode.
     *
     * @return Money
     *
     * @throws NumberFormatException      If the amount is a string in a non-supported format.
     * @throws UnknownCurrencyException   If the currency is an unknown currency code.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::UNNECESSARY, and rounding is necessary
     *                                    to represent the amount at the requested scale.
     */
    public function of($amount, $currency, ?Context $context = null, ?int $roundingMode = null) : Money
    {
        if (! $currency instanceof Currency) {
            $currency = $this->currencyProvider->getCurrency($currency);
        }

        return Money::of(
            $amount,
            $currency,
            $context ?? $this->defaultContext,
            $roundingMode ?? $this->defaultRoundingMode
        );
    }

    /**
     * Returns a Money from a number of minor units.
     *
     * By default, the money is created with a DefaultContext. This means that the amount is scaled to match the
     * currency's default fraction digits. For example, `Money::ofMinor(1234, 'USD')` will yield `USD 12.34`.
     * If the amount cannot be safely converted to this scale, an exception is thrown.
     *
     * @param BigNumber|int|float|string $minorAmount  The amount, in minor currency units.
     * @param Currency|string|int        $currency     The Currency instance, currency code or numeric currency code.
     * @param Context|null               $context      An optional Context.
     *                                                 If not provided, defaults to the factory's default context.
     * @param int|null                   $roundingMode An optional RoundingMode, if the amount does not fit the context.
     *                                                 If not provided, defaults to the factory's default rounding mode.
     *
     * @return Money
     *
     * @throws NumberFormatException      If the amount is a string in a non-supported format.
     * @throws UnknownCurrencyException   If the currency is an unknown currency code.
     * @throws RoundingNecessaryException If the rounding mode is RoundingMode::UNNECESSARY, and rounding is necessary
     *                                    to represent the amount at the requested scale.
     */
    public function ofMinor($minorAmount, $currency, ?Context $context = null, ?int $roundingMode = RoundingMode::UNNECESSARY) : Money
    {
        if (! $currency instanceof Currency) {
            $currency = $this->currencyProvider->getCurrency($currency);
        }

        return Money::ofMinor(
            $minorAmount,
            $currency,
            $context ?? $this->defaultContext,
            $roundingMode ?? $this->defaultRoundingMode
        );
    }
}
