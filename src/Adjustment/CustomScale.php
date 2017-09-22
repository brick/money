<?php

namespace Brick\Money\Adjustment;

use Brick\Money\Adjustment;
use Brick\Money\Currency;
use Brick\Money\Money;

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;

/**
 * Adjusts the scale & step of the result to custom values.
 */
class CustomScale implements Adjustment
{
    /**
     * @var int
     */
    private $scale;

    /**
     * @var int
     */
    private $step;

    /**
     * @var int
     */
    private $roundingMode;

    /**
     * @param int $scale
     * @param int $step
     * @param int $roundingMode
     */
    public function __construct($scale, $step = 1, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $this->scale        = (int) $scale;
        $this->step         = (int) $step;
        $this->roundingMode = (int) $roundingMode;
    }

    /**
     * {@inheritdoc}
     */
    public function applyTo(BigNumber $amount, Currency $currency)
    {
        if ($this->step === 1) {
            $amount = $amount->toScale($this->scale, $this->roundingMode);
        } else {
            $amount = $amount
                ->toBigRational()
                ->dividedBy($this->step)
                ->toScale($this->scale, $this->roundingMode)
                ->multipliedBy($this->step);
        }

        return new Money($amount, $currency, $this->step);
    }
}
