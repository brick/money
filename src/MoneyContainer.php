<?php

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
     * @return BigNumber[]
     */
    public function getAmounts();
}
