<?php

declare(strict_types=1);

namespace Brick\Money\Context;

use Brick\Money\Context;
use Brick\Money\Currency;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;

/**
 * Adjusts a number to the default scale for the currency.
 *
 * @psalm-immutable
 */
final class DefaultContext implements Context
{
    /**
     * @inheritdoc
     */
    public function applyTo(BigNumber $amount, Currency $currency, RoundingMode $roundingMode) : BigDecimal
    {
        return $amount->toScale($currency->getDefaultFractionDigits(), $roundingMode);
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
        return true;
    }
}
