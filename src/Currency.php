<?php

declare(strict_types=1);

namespace Brick\Money;
use JsonSerializable;
use Stringable;

interface Currency extends Stringable, JsonSerializable
{
    public function getCode(): string;

    public function getDefaultFractionDigits(): int;

    public function is(Currency $currency) : bool;
}
