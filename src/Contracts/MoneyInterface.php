<?php

declare(strict_types=1);

namespace Brick\Money\Contracts;

use Brick\Math\BigNumber;
use Brick\Money\Currency;

/**
 * Contract interface for all objects representing a money.
 *
 * @author Ang3^ <https://github.com/Ang3>
 */
interface MoneyInterface
{
	/**
	 * Returns the amount of this Money, as a BigNumber.
	 *
	 * @return BigNumber
	 */
	public function getAmount(): BigNumber;

	/**
	 * Returns the Currency of this Money.
	 *
	 * @return Currency
	 */
	public function getCurrency(): Currency;
}
