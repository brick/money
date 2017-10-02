<?php

declare(strict_types=1);

namespace Brick\Money\Context;

use Brick\Money\Context;
use Brick\Money\Currency;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;

/**
 * Automatically adjusts the scale of a number to the strict minimum.
 */
final class AutoContext implements Context
{
    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency, int $roundingMode) : BigDecimal
    {
        if ($roundingMode !== RoundingMode::UNNECESSARY) {
            throw new \InvalidArgumentException('AutoContext only supports RoundingMode::UNNECESSARY');
        }

        return $amount->toBigDecimal()->stripTrailingZeros();
    }

    /**
     * {@inheritdoc}
     */
    public function getStep() : int
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function isFixedScale() : bool
    {
        return false;
    }
}
