<?php

declare(strict_types=1);

namespace Brick\Money\Contracts;

use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Brick\Money\Exception\MoneyMismatchException;

/**
 * Interface implemented by objects allowing money operations.
 *
 * @author Ang3^ <https://github.com/Ang3>
 */
interface Monetizable
{
	/**
	 * Returns the sum of this Money and the given amount.
	 *
	 * If the operand is a Money, it must have the same context as this Money, or an exception is thrown.
	 * This is by design, to ensure that contexts are not mixed accidentally.
	 * If you do need to add a Money in a different context, you can use `plus($money->toRational())`.
	 *
	 * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
	 * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
	 * thrown.
	 *
	 * @psalm-param RoundingMode::* $roundingMode
	 *
	 * @param MoneyInterface|BigNumber|int|float|string $that         The money or amount to add.
	 * @param int                                       $roundingMode An optional RoundingMode constant.
	 *
	 * @return self
	 *
	 * @throws MathException          If the argument is an invalid number or rounding is necessary.
	 * @throws MoneyMismatchException If the argument is a money in a different currency or in a different context.
	 */
	public function plus(MoneyInterface|BigNumber|int|float|string $that, int $roundingMode = RoundingMode::UNNECESSARY): self;


	/**
	 * Returns the difference of this Money and the given amount.
	 *
	 * If the operand is a Money, it must have the same context as this Money, or an exception is thrown.
	 * This is by design, to ensure that contexts are not mixed accidentally.
	 * If you do need to subtract a Money in a different context, you can use `minus($money->toRational())`.
	 *
	 * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
	 * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
	 * thrown.
	 *
	 * @psalm-param RoundingMode::* $roundingMode
	 *
	 * @param MoneyInterface|BigNumber|int|float|string $that         The money or amount to subtract.
	 * @param int                                       $roundingMode An optional RoundingMode constant.
	 *
	 * @return self
	 *
	 * @throws MathException          If the argument is an invalid number or rounding is necessary.
	 * @throws MoneyMismatchException If the argument is a money in a different currency or in a different context.
	 */
	public function minus(MoneyInterface|BigNumber|int|float|string $that, int $roundingMode = RoundingMode::UNNECESSARY): self;

	/**
	 * Returns the product of this Money and the given number.
	 *
	 * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
	 * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
	 * thrown.
	 *
	 * @psalm-param RoundingMode::* $roundingMode
	 *
	 * @param BigNumber|int|float|string $that         The multiplier.
	 * @param int                        $roundingMode An optional RoundingMode constant.
	 *
	 * @return self
	 *
	 * @throws MathException If the argument is an invalid number or rounding is necessary.
	 */
	public function multipliedBy(BigNumber|int|float|string $that, int $roundingMode = RoundingMode::UNNECESSARY): self;

	/**
	 * Returns the result of the division of this Money by the given number.
	 *
	 * The resulting Money has the same context as this Money. If the result needs rounding to fit this context, a
	 * rounding mode can be provided. If a rounding mode is not provided and rounding is necessary, an exception is
	 * thrown.
	 *
	 * @psalm-param RoundingMode::* $roundingMode
	 *
	 * @param BigNumber|int|float|string $that         The divisor.
	 * @param int                        $roundingMode An optional RoundingMode constant.
	 *
	 * @return self
	 *
	 * @throws MathException If the argument is an invalid number or is zero, or rounding is necessary.
	 */
	public function dividedBy(BigNumber|int|float|string $that, int $roundingMode = RoundingMode::UNNECESSARY): self;

	/**
	 * Returns a Money whose value is the absolute value of this Money.
	 *
	 * The resulting Money has the same context as this Money.
	 *
	 * @return self
	 */
	public function abs(): self;
	/**
	 * Returns a Money whose value is the negated value of this Money.
	 *
	 * @return self
	 */
	public function negated() : self;

	/**
	 * Compares this money to the given amount.
	 *
	 * @return int [-1, 0, 1] if `$this` is less than, equal to, or greater than `$that`.
	 *
	 * @throws MathException          If the argument is an invalid number.
	 * @throws MoneyMismatchException If the argument is a money in a different currency.
	 */
	public function compareTo(MoneyInterface|BigNumber|int|float|string $that): int;

	/**
	 * Returns whether this money is equal to the given amount.
	 *
	 * @throws MathException          If the argument is an invalid number.
	 * @throws MoneyMismatchException If the argument is a money in a different currency.
	 */
	public function isEqualTo(MoneyInterface|BigNumber|int|float|string $that): bool;

	/**
	 * Returns whether this money is less than the given amount.
	 *
	 * @throws MathException          If the argument is an invalid number.
	 * @throws MoneyMismatchException If the argument is a money in a different currency.
	 */
	public function isLessThan(MoneyInterface|BigNumber|int|float|string $that): bool;

	/**
	 * Returns whether this money is less than or equal to the given amount.
	 *
	 * @throws MathException          If the argument is an invalid number.
	 * @throws MoneyMismatchException If the argument is a money in a different currency.
	 */
	public function isLessThanOrEqualTo(MoneyInterface|BigNumber|int|float|string $that): bool;

	/**
	 * Returns whether this money is greater than the given amount.
	 *
	 * @throws MathException          If the argument is an invalid number.
	 * @throws MoneyMismatchException If the argument is a money in a different currency.
	 */
	public function isGreaterThan(MoneyInterface|BigNumber|int|float|string $that): bool;

	/**
	 * Returns whether this money is greater than or equal to the given amount.
	 *
	 * @throws MathException          If the argument is an invalid number.
	 * @throws MoneyMismatchException If the argument is a money in a different currency.
	 */
	public function isGreaterThanOrEqualTo(MoneyInterface|BigNumber|int|float|string $that): bool;

	/**
	 * Returns whether this money's amount and currency are equal to those of the given money.
	 *
	 * Unlike isEqualTo(), this method only accepts a money, and returns false if the given money is in another
	 * currency, instead of throwing a MoneyMismatchException.
	 *
	 * @param MoneyInterface $that
	 *
	 * @return bool
	 */
	public function isAmountAndCurrencyEqualTo(MoneyInterface $that): bool;

	/**
	 * Returns whether this money has zero value.
	 *
	 * @return bool
	 */
	public function isZero(): bool;

	/**
	 * Returns whether this money has a positive value.
	 *
	 * @return bool
	 */
	public function isPositive(): bool;

	/**
	 * Returns whether this money has a positive or zero value.
	 *
	 * @return bool
	 */
	public function isPositiveOrZero(): bool;

	/**
	 * Returns whether this money has a negative value.
	 *
	 * @return bool
	 */
	public function isNegative(): bool;

	/**
	 * Returns whether this money has a negative or zero value.
	 *
	 * @return bool
	 */
	public function isNegativeOrZero(): bool;
}
