<?php

declare(strict_types=1);

namespace Brick\Money\Allocation;

use Brick\Math\BigInteger;

/**
 * @internal
 */
final class FloorSeparateStrategy extends AbstractFloorStrategy
{
    /**
     * @param list<BigInteger> $ratios
     *
     * @return list<BigInteger>
     */
    public function allocate(BigInteger $amountInSteps, array $ratios): array
    {
        [$floors, $remainderSteps] = $this->computeFloors($amountInSteps, $ratios);

        return [...$floors, $remainderSteps];
    }
}
