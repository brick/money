<?php

namespace Brick\Money;

/**
 * Common interface for Money and MoneyBag.
 */
interface MoneyContainer
{
    /**
     * Returns the contained monies.
     *
     * @return Money[]
     */
    public function getMonies();
}
