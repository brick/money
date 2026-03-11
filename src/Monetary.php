<?php

declare(strict_types=1);

namespace Brick\Money;

/**
 * Common interface for Money, RationalMoney, and MoneyBag.
 *
 * @phpstan-sealed AbstractMoney|MoneyBag
 */
interface Monetary
{
    /**
     * Returns the monies contained in this object, sorted by currency code.
     *
     * @return list<RationalMoney>
     *
     * @pure
     */
    public function getMonies(): array;
}
