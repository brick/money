<?php

declare(strict_types=1);

namespace Brick\Money\Allocation;

use Brick\Math\BigInteger;

use function array_keys;

/**
 * @internal
 */
final class FloorToFirstStrategy extends AbstractFloorAbsorbStrategy
{
    /**
     * @param list<BigInteger> $ratios
     *
     * @return list<int>
     *
     * @pure
     */
    protected function priorityIndices(BigInteger $amountInSteps, array $ratios): array
    {
        return array_keys($ratios);
    }
}
