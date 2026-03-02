<?php

declare(strict_types=1);

namespace Brick\Money\Context;

use Brick\Math\BigInteger;

/**
 * @internal
 */
trait StepValidation
{
    /**
     * @param positive-int     $step
     * @param non-negative-int $scale
     */
    private function isValidStepForScale(int $step, int $scale): bool
    {
        $step = BigInteger::of($step);
        $power = BigInteger::ten()->power($scale);

        return $power->mod($step)->isZero() || $step->mod($power)->isZero();
    }
}
