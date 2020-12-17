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
     * @psalm-return array<string, BigNumber>
     *
     * @return BigNumber[]
     */
    public function getAmounts() : array;
}
