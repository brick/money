<?php

declare(strict_types=1);

namespace Brick\Money;

/**
 * Common interface for Money, RationalMoney and MoneyBag.
 */
interface Monetary
{
    /**
     * @return list<RationalMoney>
     */
    public function getMonies(): array;
}
