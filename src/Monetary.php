<?php

declare(strict_types=1);

namespace Brick\Money;

/**
 * Common interface for Money, RationalMoney, and MoneyBag.
 *
 * This interface is sealed: implementing it in userland code is not supported, and breaking changes to this interface
 * can happen at any time, even in minor or patch releases.
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
