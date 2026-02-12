<?php

declare(strict_types=1);

namespace Brick\Money\Tests\Context;

use Brick\Math\BigNumber;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Brick\Money\Context\CashContext;
use Brick\Money\Currency;
use Brick\Money\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for class CashContext.
 */
class CashContextTest extends AbstractTestCase
{
    #[DataProvider('providerApplyTo')]
    public function testApplyTo(int $step, string $amount, string $currency, RoundingMode $roundingMode, string $expected): void
    {
        $amount = BigNumber::of($amount);
        $currency = Currency::of($currency);

        $context = new CashContext($step);

        if (self::isExceptionClass($expected)) {
            $this->expectException($expected);
        }

        $actual = $context->applyTo($amount, $currency, $roundingMode);

        if (! self::isExceptionClass($expected)) {
            self::assertBigDecimalIs($expected, $actual);
        }
    }

    public static function providerApplyTo(): array
    {
        return [
            [1, '1', 'USD', RoundingMode::Unnecessary, '1.00'],
            [1, '1.001', 'USD', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [1, '1.001', 'USD', RoundingMode::Down, '1.00'],
            [1, '1.001', 'USD', RoundingMode::Up, '1.01'],
            [1, '1', 'JPY', RoundingMode::Unnecessary, '1'],
            [1, '1.00', 'JPY', RoundingMode::Unnecessary, '1'],
            [1, '1.01', 'JPY', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [1, '1.01', 'JPY', RoundingMode::Down, '1'],
            [1, '1.01', 'JPY', RoundingMode::Up, '2'],
            [5, '1', 'CHF', RoundingMode::Unnecessary, '1.00'],
            [5, '1.05', 'CHF', RoundingMode::Unnecessary, '1.05'],
            [5, '1.07', 'CHF', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [5, '1.07', 'CHF', RoundingMode::Down, '1.05'],
            [5, '1.07', 'CHF', RoundingMode::Up, '1.10'],
            [5, '1.075', 'CHF', RoundingMode::HalfDown, '1.05'],
            [5, '1.075', 'CHF', RoundingMode::HalfUp, '1.10'],
            [100, '-1', 'CZK', RoundingMode::Unnecessary, '-1.00'],
            [100, '-1.00', 'CZK', RoundingMode::Unnecessary, '-1.00'],
            [100, '-1.5', 'CZK', RoundingMode::Unnecessary, RoundingNecessaryException::class],
            [100, '-1.5', 'CZK', RoundingMode::Down, '-1.00'],
            [100, '-1.5', 'CZK', RoundingMode::Up, '-2.00'],
        ];
    }

    public function testGetStep(): void
    {
        $context = new CashContext(5);
        self::assertSame(5, $context->getStep());
    }
}
