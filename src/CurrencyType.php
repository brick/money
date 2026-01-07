<?php

declare(strict_types=1);

namespace Brick\Money;

enum CurrencyType
{
    case IsoCurrent;
    case IsoHistorical;
    case Custom;
}
