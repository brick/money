<?php

namespace Brick\Money;

use Brick\Math\BigNumber;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\RoundingNecessaryException;

/**
 * Defines how many fraction digits are stored in monies, and how they are rounded.
 */
class MoneyContext
{
    /**
     * The number of fraction digits in a Money.
     *
     * @var int
     */
    private $scale;

    /**
     * The rouding mode for the amount of a Money.
     *
     * @var int
     */
    private $roundingMode;

    /**
     * Private constructor. Use a factory method to obtain an instance.
     *
     * @param int  $scale        The scale of the amount.
     * @param int  $roundingMode The rounding mode.
     */
    private function __construct($scale, $roundingMode)
    {
        $this->scale        = $scale;
        $this->roundingMode = $roundingMode;
    }

    /**
     * Returns a context with a fixed scale of the given Money's scale.
     *
     * @param Money $money
     * @param int   $roundingMode
     *
     * @return MoneyContext
     */
    public static function scaleOf(Money $money, $roundingMode = RoundingMode::UNNECESSARY)
    {
        return new MoneyContext($money->getAmount()->scale(), $roundingMode);
    }

    /**
     * Returns a context with a fixed scale of the given Currency's default fraction digits.
     *
     * @param Currency $currency
     * @param int      $roundingMode
     *
     * @return MoneyContext
     */
    public static function defaultScale(Currency $currency, $roundingMode = RoundingMode::UNNECESSARY)
    {
        return new MoneyContext($currency->getDefaultFractionDigits(), $roundingMode);
    }

    /**
     * @param int $scale
     * @param int $roundingMode
     *
     * @return MoneyContext
     */
    public static function fixedScale($scale, $roundingMode = RoundingMode::UNNECESSARY)
    {
        $scale        = (int) $scale;
        $roundingMode = (int) $roundingMode;

        self::checkScale($scale);
        self::checkRoundingMode($roundingMode);

        return new MoneyContext((int) $scale, (int) $roundingMode);
    }

    /**
     * @param int $scale
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private static function checkScale($scale)
    {
        if ($scale < 0) {
            throw new \InvalidArgumentException('The scale must be zero or more.');
        }
    }

    /**
     * @param int $roundingMode
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private static function checkRoundingMode($roundingMode)
    {
        static $roundingModes;

        if ($roundingModes === null) {
            $roundingModeClass = new \ReflectionClass(RoundingMode::class);
            $roundingModes = $roundingModeClass->getConstants();
        }

        if (! in_array($roundingMode, $roundingModes, true)) {
            throw new \InvalidArgumentException('Invalid rounding mode provided.');
        }
    }

    /**
     * @return int
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @return int
     */
    public function getRoundingMode()
    {
        return $this->roundingMode;
    }

    /**
     * @param BigNumber $amount
     *
     * @return BigDecimal
     *
     * @throws RoundingNecessaryException
     */
    public function applyTo(BigNumber $amount)
    {
        return $amount->toScale($this->scale, $this->roundingMode);
    }
}
