<?php

declare(strict_types=1);

namespace Brick\Money\Allocation;

use Brick\Math\BigInteger;

use function array_keys;
use function usort;

/**
 * @internal
 */
final class FloorToLargestRatioStrategy extends AbstractFloorAbsorbStrategy
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
        $indices = array_keys($ratios);

        usort($indices, fn (int $a, int $b): int => $ratios[$b]->compareTo($ratios[$a]) ?: $a <=> $b);

        return $indices;
    }
}
