<?php

declare(strict_types=1);

namespace Brick\Money\Tests;

use Brick\Math\BigNumber;
use Brick\Money\ComparisonMode\BaseCurrencyComparisonMode;
use Brick\Money\ComparisonMode\PairwiseComparisonMode;
use Brick\Money\Context\AutoContext;
use Brick\Money\Currency;
use Brick\Money\Exception\ExchangeRateNotFoundException;
use Brick\Money\ExchangeRateProvider;
use Brick\Money\ExchangeRateProvider\ConfigurableProvider;
use Brick\Money\Money;
use Brick\Money\MoneyComparator;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_map;

/**
 * Tests for class MoneyComparator.
 */
class MoneyComparatorTest extends AbstractTestCase
{
    /**
     * @param array      $a   The money to compare.
     * @param array      $b   The money to compare to.
     * @param int|string $cmp The expected comparison value, or an exception class.
     */
    #[DataProvider('providerCompare')]
    public function testCompare(array $a, array $b, int|string $cmp): void
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider(), new PairwiseComparisonMode());

        $a = Money::of(...$a);
        $b = Money::of(...$b);

        if (self::isExceptionClass($cmp)) {
            $this->expectException($cmp);
        }

        self::assertSame($cmp, $comparator->compare($a, $b));
        self::assertSame($cmp < 0, $comparator->isLess($a, $b));
        self::assertSame($cmp > 0, $comparator->isGreater($a, $b));
        self::assertSame($cmp <= 0, $comparator->isLessOrEqual($a, $b));
        self::assertSame($cmp >= 0, $comparator->isGreaterOrEqual($a, $b));
        self::assertSame($cmp === 0, $comparator->isEqual($a, $b));
    }

    public static function providerCompare(): array
    {
        return [
            [['1.00', 'EUR'], ['1', 'EUR'], 0],

            [['1.00', 'EUR'], ['1.09', 'USD'], 1],
            [['1.00', 'EUR'], ['1.10', 'USD'], 0],
            [['1.00', 'EUR'], ['1.11', 'USD'], -1],

            [['1.11', 'USD'], ['1.00', 'EUR'], -1],
            [['1.12', 'USD'], ['1.00', 'EUR'], 1],

            [['123.57', 'USD'], ['123.57', 'BSD'], 0],
            [['123.57', 'BSD'], ['123.57', 'USD'], 0],

            [['1000250.123456', 'EUR', new AutoContext()], ['800200.0987648', 'GBP', new AutoContext()], 0],
            [['1000250.123456', 'EUR', new AutoContext()], ['800200.098764', 'GBP', new AutoContext()], 1],
            [['1000250.123456', 'EUR', new AutoContext()], ['800200.098765', 'GBP', new AutoContext()], -1],

            [['800200.098764', 'GBP', new AutoContext()], ['1000250.123456', 'EUR', new AutoContext()], -1],
            [['800200.098764', 'GBP', new AutoContext()], ['960240.1185168000', 'EUR', new AutoContext()], 0],
            [['800200.098764', 'GBP', new AutoContext()], ['960240.118516', 'EUR', new AutoContext()], 1],
            [['800200.098764', 'GBP', new AutoContext()], ['960240.118517', 'EUR', new AutoContext()], -1],

            [['1.0', 'EUR'], ['1.0', 'BSD'], ExchangeRateNotFoundException::class],
        ];
    }

    /**
     * @param array  $monies      The monies to compare.
     * @param string $expectedMin The expected minimum money, or an exception class.
     */
    #[DataProvider('providerMin')]
    public function testMin(array $monies, string $expectedMin): void
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider(), new PairwiseComparisonMode());

        $monies = array_map(
            fn (array $money) => Money::of(...$money),
            $monies,
        );

        if (self::isExceptionClass($expectedMin)) {
            $this->expectException($expectedMin);
        }

        $actualMin = $comparator->min(...$monies);

        if (! self::isExceptionClass($expectedMin)) {
            self::assertMoneyIs($expectedMin, $actualMin);
        }
    }

    public static function providerMin(): array
    {
        return [
            [[['1.00', 'EUR'], ['1.09', 'USD']], 'USD 1.09'],
            [[['1.00', 'EUR'], ['1.10', 'USD']], 'EUR 1.00'],
            [[['1.00', 'EUR'], ['1.11', 'USD']], 'EUR 1.00'],
            [[['1.00', 'EUR'], ['1.09', 'USD'], ['1.20', 'BSD']], 'USD 1.09'],
            [[['1.00', 'EUR'], ['1.12', 'USD'], ['1.20', 'BSD']], ExchangeRateNotFoundException::class],
            [[['1.05', 'EUR'], ['1.00', 'GBP'], ['1.19', 'EUR']], 'EUR 1.05'],
        ];
    }

    /**
     * @param array  $monies      The monies to compare.
     * @param string $expectedMin The expected maximum money, or an exception class.
     */
    #[DataProvider('providerMax')]
    public function testMax(array $monies, string $expectedMin): void
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider(), new PairwiseComparisonMode());

        $monies = array_map(
            fn (array $money) => Money::of(...$money),
            $monies,
        );

        if (self::isExceptionClass($expectedMin)) {
            $this->expectException($expectedMin);
        }

        $actualMin = $comparator->max(...$monies);

        if (! self::isExceptionClass($expectedMin)) {
            self::assertMoneyIs($expectedMin, $actualMin);
        }
    }

    public static function providerMax(): array
    {
        return [
            [[['1.00', 'EUR'], ['1.09', 'USD']], 'EUR 1.00'],
            [[['1.00', 'EUR'], ['1.10', 'USD']], 'EUR 1.00'],
            [[['1.00', 'EUR'], ['1.11', 'USD']], 'USD 1.11'],
            [[['1.00', 'EUR'], ['1.09', 'USD'], ['1.20', 'BSD']], ExchangeRateNotFoundException::class],
            [[['1.00', 'EUR'], ['1.22', 'USD'], ['1.20', 'BSD']], 'USD 1.22'],
            [[['1.00', 'EUR'], ['1.12', 'USD'], ['1.20', 'BSD']], 'BSD 1.20'],
            [[['1.05', 'EUR'], ['1.00', 'GBP'], ['1.19', 'EUR']], 'GBP 1.00'],
            [[['1.05', 'EUR'], ['1.00', 'GBP'], ['1.2001', 'EUR', new AutoContext()]], 'EUR 1.2001'],
        ];
    }

    public function testCompareWithDimensionsFromConstructor(): void
    {
        $provider = new class() implements ExchangeRateProvider {
            /**
             * @var list<array<string, mixed>>
             */
            public array $seenDimensions = [];

            public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
            {
                $this->seenDimensions[] = $dimensions;

                if (($dimensions['rateType'] ?? null) !== 'spot') {
                    return null;
                }

                return BigNumber::of('1.1');
            }
        };

        $a = Money::of('1.00', 'EUR');
        $b = Money::of('1.10', 'USD');

        $withoutDimensions = new MoneyComparator($provider, new PairwiseComparisonMode());
        $this->expectException(ExchangeRateNotFoundException::class);
        $withoutDimensions->compare($a, $b);
    }

    public function testCompareWithDimensionsFromConstructorSucceeds(): void
    {
        $provider = new class() implements ExchangeRateProvider {
            /**
             * @var list<array<string, mixed>>
             */
            public array $seenDimensions = [];

            public function getExchangeRate(Currency $sourceCurrency, Currency $targetCurrency, array $dimensions = []): ?BigNumber
            {
                $this->seenDimensions[] = $dimensions;

                if (($dimensions['rateType'] ?? null) !== 'spot') {
                    return null;
                }

                return BigNumber::of('1.1');
            }
        };

        $a = Money::of('1.00', 'EUR');
        $b = Money::of('1.10', 'USD');

        $withDimensions = new MoneyComparator($provider, new PairwiseComparisonMode(), ['rateType' => 'spot']);

        self::assertSame(0, $withDimensions->compare($a, $b));
        self::assertSame([
            ['rateType' => 'spot'],
        ], $provider->seenDimensions);
    }

    public function testCompareWithBaseCurrencyString(): void
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider(), new BaseCurrencyComparisonMode('USD'));

        // EUR 1.00 → USD = 1.00 * 1.1 = 1.10
        self::assertSame(0, $comparator->compare(Money::of('1.00', 'EUR'), Money::of('1.10', 'USD')));
        self::assertSame(1, $comparator->compare(Money::of('1.00', 'EUR'), Money::of('1.09', 'USD')));
        self::assertSame(-1, $comparator->compare(Money::of('1.00', 'EUR'), Money::of('1.11', 'USD')));
    }

    public function testCompareWithBaseCurrencyObject(): void
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider(), new BaseCurrencyComparisonMode(Currency::of('USD')));

        self::assertSame(0, $comparator->compare(Money::of('1.10', 'USD'), Money::of('1.00', 'EUR')));
    }

    public function testBaseCurrencyModeVsPairwiseModeWithAsymmetricRates(): void
    {
        // EUR→USD = 1.1, USD→EUR = 0.9 (asymmetric)
        //
        // Pairwise: compare(EUR 1.00, USD 1.11) converts EUR to USD → 1.10, which is < 1.11 → result: -1
        // Base=EUR:  EUR 1.00 stays 1.00; USD 1.11 → EUR = 1.11 * 0.9 = 0.999 → result: +1
        //
        // The two modes legitimately disagree when rates are asymmetric.
        $provider = $this->getExchangeRateProvider();

        $eur = Money::of('1.00', 'EUR');
        $usd = Money::of('1.11', 'USD');

        $pairwise = new MoneyComparator($provider, new PairwiseComparisonMode());
        self::assertSame(-1, $pairwise->compare($eur, $usd));

        $baseCurrency = new MoneyComparator($provider, new BaseCurrencyComparisonMode('EUR'));
        self::assertSame(1, $baseCurrency->compare($eur, $usd));
    }

    public function testMinWithBaseCurrency(): void
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider(), new BaseCurrencyComparisonMode('USD'));

        // EUR 1.00 → USD 1.10; USD 1.09 is less
        self::assertMoneyIs('USD 1.09', $comparator->min(Money::of('1.00', 'EUR'), Money::of('1.09', 'USD')));

        // EUR 1.00 → USD 1.10; USD 1.11 is more, so EUR 1.00 is min
        self::assertMoneyIs('EUR 1.00', $comparator->min(Money::of('1.00', 'EUR'), Money::of('1.11', 'USD')));
    }

    public function testMaxWithBaseCurrency(): void
    {
        $comparator = new MoneyComparator($this->getExchangeRateProvider(), new BaseCurrencyComparisonMode('USD'));

        // EUR 1.00 → USD 1.10; USD 1.09 is less, so EUR 1.00 is max
        self::assertMoneyIs('EUR 1.00', $comparator->max(Money::of('1.00', 'EUR'), Money::of('1.09', 'USD')));

        // EUR 1.00 → USD 1.10; USD 1.11 is more
        self::assertMoneyIs('USD 1.11', $comparator->max(Money::of('1.00', 'EUR'), Money::of('1.11', 'USD')));
    }

    public function testBaseCurrencyRateNotFound(): void
    {
        // No GBP→USD rate in the provider
        $comparator = new MoneyComparator($this->getExchangeRateProvider(), new BaseCurrencyComparisonMode('USD'));

        $this->expectException(ExchangeRateNotFoundException::class);
        $comparator->compare(Money::of('1.00', 'GBP'), Money::of('1.00', 'EUR'));
    }

    private function getExchangeRateProvider(): ConfigurableProvider
    {
        $eur = Currency::of('EUR');
        $usd = Currency::of('USD');
        $gbp = Currency::of('GBP');
        $bsd = Currency::of('BSD');

        return ConfigurableProvider::builder()
            ->addExchangeRate($eur, $usd, BigNumber::of('1.1'))
            ->addExchangeRate($usd, $eur, BigNumber::of('0.9'))
            ->addExchangeRate($usd, $bsd, BigNumber::of(1))
            ->addExchangeRate($bsd, $usd, BigNumber::of(1))
            ->addExchangeRate($eur, $gbp, BigNumber::of('0.8'))
            ->addExchangeRate($gbp, $eur, BigNumber::of('1.2'))
            ->build();
    }
}
