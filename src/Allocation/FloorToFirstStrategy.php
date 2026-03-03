<?php

declare(strict_types=1);

namespace Brick\Money\Allocation;

use Brick\Math\BigInteger;

use function array_keys;

/**
 * @internal
 */
final readonly class FloorToFirstStrategy extends AbstractFloorAbsorbStrategy
{
    protected function prioritizeIndices(BigInteger $amountInSteps, array $ratios): array
    {
        return array_keys($ratios);
    }
}
