<?php

declare(strict_types=1);

namespace Brick\Money;

use Brick\Math\BigNumber;

/**
 * Common interface for Money, RationalMoney and MoneyBag.
 */
interface MoneyContainer
{
    /**
     * Returns the amounts contained in this money container, indexed by currency code.
     *
     * @return array<string, BigNumber>
     */
    public function getAmounts() : array;
}
