<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Exception\ContextException;
use Brick\Money\Exception\CurrencyMismatchException;
use JsonSerializable;
use Override;
use Stringable;

/**
 * Base class for Money and RationalMoney.
 *
 * This class is sealed: extending this class yourself is not supported, and breaking changes affecting subclasses, such
 * as adding new abstract methods or updating / removing protected methods, can happen at any time, even in minor or
 * patch releases.
 *
 * @phpstan-sealed Money|RationalMoney
 */
abstract readonly class AbstractMoney implements Monetary, Stringable, JsonSerializable
{
    /**
     * @pure
     */
    abstract public function getAmount(): BigNumber;

    /**
     * @pure
     */
    abstract public function getCurrency(): Currency;

    /**
     * Converts this money to a Money in the given Context.
     *
     * @param Context      $context      The context.
     * @param RoundingMode $roundingMode The rounding mode, if necessary.
     *
     * @throws RoundingNecessaryException If RoundingMode::Unnecessary is used but rounding is necessary.
     * @throws ContextException           If the context does not apply.
     *
     * @pure
     */
    final public function toContext(Context $context, RoundingMode $roundingMode = RoundingMode::Unnecessary): Money
    {
        return Money::create($this->getAmount(), $this->getCurrency(), $context, $roundingMode);
    }

    /**
     * This method satisfies the Monetary interface.
     */
    #[Override]
    final public function getMonies(): array
    {
        if ($this->isZero()) {
            return [];
        }

        return [
            $this->toRational(),
        ];
    }

    /**
     * Returns the sign of this money.
     *
     * @return -1|0|1 -1 if the number is negative, 0 if zero, 1 if positive.
     *
     * @pure
     */
    final public function getSign(): int
    {
        return $this->getAmount()->getSign();
    }

    /**
     * Returns whether this money has zero value.
     *
     * @pure
     */
    final public function isZero(): bool
    {
        return $this->getAmount()->isZero();
    }

    /**
     * Returns whether this money has a negative value.
     *
     * @pure
     */
    final public function isNegative(): bool
    {
        return $this->getAmount()->isNegative();
    }

    /**
     * Returns whether this money has a negative or zero value.
     *
     * @pure
     */
    final public function isNegativeOrZero(): bool
    {
        return $this->getAmount()->isNegativeOrZero();
    }

    /**
     * Returns whether this money has a positive value.
     *
     * @pure
     */
    final public function isPositive(): bool
    {
        return $this->getAmount()->isPositive();
    }

    /**
     * Returns whether this money has a positive or zero value.
     *
     * @pure
     */
    final public function isPositiveOrZero(): bool
    {
        return $this->getAmount()->isPositiveOrZero();
    }

    /**
     * Compares this money to the given amount.
     *
     * Only the amount and currency are compared; context is not checked. Two Money instances with the same currency
     * and amount but different contexts compare as equal. This is intentional: the comparison result is
     * mathematically well-defined regardless of context.
     *
     * @return -1|0|1 If `$this` is less than, equal to, or greater than `$that`.
     *
     * @throws MathException             If the argument is an invalid number.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     *
     * @pure
     */
    final public function compareTo(AbstractMoney|BigNumber|int|string $that): int
    {
        return $this->getAmount()->compareTo($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is equal to the given amount.
     *
     * This method throws an exception if the argument is a money in a different currency.
     * If you want to return false when the currencies differ, use isSameValueAs() instead.
     *
     * @throws MathException             If the argument is an invalid number.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     *
     * @pure
     */
    final public function isEqualTo(AbstractMoney|BigNumber|int|string $that): bool
    {
        return $this->getAmount()->isEqualTo($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is less than the given amount.
     *
     * Only the amount and currency are compared; context is not checked. See compareTo() for details.
     *
     * @throws MathException             If the argument is an invalid number.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     *
     * @pure
     */
    final public function isLessThan(AbstractMoney|BigNumber|int|string $that): bool
    {
        return $this->getAmount()->isLessThan($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is less than or equal to the given amount.
     *
     * Only the amount and currency are compared; context is not checked. See compareTo() for details.
     *
     * @throws MathException             If the argument is an invalid number.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     *
     * @pure
     */
    final public function isLessThanOrEqualTo(AbstractMoney|BigNumber|int|string $that): bool
    {
        return $this->getAmount()->isLessThanOrEqualTo($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is greater than the given amount.
     *
     * Only the amount and currency are compared; context is not checked. See compareTo() for details.
     *
     * @throws MathException             If the argument is an invalid number.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     *
     * @pure
     */
    final public function isGreaterThan(AbstractMoney|BigNumber|int|string $that): bool
    {
        return $this->getAmount()->isGreaterThan($this->getAmountOf($that));
    }

    /**
     * Returns whether this money is greater than or equal to the given amount.
     *
     * Only the amount and currency are compared; context is not checked. See compareTo() for details.
     *
     * @throws MathException             If the argument is an invalid number.
     * @throws CurrencyMismatchException If the argument is a money in a different currency.
     *
     * @pure
     */
    final public function isGreaterThanOrEqualTo(AbstractMoney|BigNumber|int|string $that): bool
    {
        return $this->getAmount()->isGreaterThanOrEqualTo($this->getAmountOf($that));
    }

    /**
     * Returns whether this money's amount and currency are equal to those of the given money.
     *
     * Unlike isEqualTo(), this method only accepts a money, and returns false if the given money is in another
     * currency, instead of throwing a CurrencyMismatchException.
     *
     * @pure
     */
    final public function isSameValueAs(AbstractMoney $that): bool
    {
        return $this->getAmount()->isEqualTo($that->getAmount())
            && $this->getCurrency()->isEqualTo($that->getCurrency());
    }

    /**
     * @return array{amount: string, currency: string}
     *
     * @pure
     */
    #[Override]
    final public function jsonSerialize(): array
    {
        return [
            'amount' => $this->getAmount()->toString(),
            'currency' => $this->getCurrency()->getCurrencyCode(),
        ];
    }

    /**
     * Returns a money whose value is the absolute value of this money.
     *
     * @pure
     */
    abstract public function abs(): static;

    /**
     * Returns a money whose value is the negated value of this money.
     *
     * @pure
     */
    abstract public function negated(): static;

    /**
     * Converts this money to a RationalMoney.
     *
     * @pure
     */
    abstract public function toRational(): RationalMoney;

    /**
     * Returns the amount of the given parameter.
     *
     * If the parameter is a money, its currency is checked against this money's currency.
     *
     * @param AbstractMoney|BigNumber|int|string $that A money or amount.
     *
     * @throws CurrencyMismatchException If currencies don't match.
     *
     * @pure
     */
    final protected function getAmountOf(AbstractMoney|BigNumber|int|string $that): BigNumber|int|string
    {
        if ($that instanceof AbstractMoney) {
            if (! $that->getCurrency()->isEqualTo($this->getCurrency())) {
                throw CurrencyMismatchException::currencyMismatch($this->getCurrency(), $that->getCurrency());
            }

            return $that->getAmount();
        }

        return $that;
    }
}
