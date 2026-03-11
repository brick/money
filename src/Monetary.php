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
     * Returns the non-zero monetary components of this value, sorted by currency code.
     *
     * There must be at most one money per currency. Zero monies are not included.
     *
     * @return list<RationalMoney>
     *
     * @pure
     */
    public function getMonies(): array;
}
