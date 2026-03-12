<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\Exception\ExchangeRateProviderException;
use Brick\Money\Exception\InvalidArgumentException;
use Brick\Money\ExchangeRateProvider\Pdo\SqlCondition;
use Brick\Money\ExchangeRateProvider\PdoProvider;
use Brick\Money\Tests\AbstractTestCase;
use DateTimeImmutable;
use DateTimeInterface;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

/**
 * Tests for class PdoProvider.
 */
#[RequiresPhpExtension('pdo_sqlite')]
class PdoProviderTest extends AbstractTestCase
{
    public function testBuilderRequiresSourceSelector(): void
    {
        $pdo = new PDO('sqlite::memory:');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A source currency selector must be configured using setFixedSourceCurrency() or setSourceCurrencyColumn().',
        );

        PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setTargetCurrencyColumn('target_currency')
            ->build();
    }

    public function testBuilderRequiresTargetSelector(): void
    {
        $pdo = new PDO('sqlite::memory:');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A target currency selector must be configured using setFixedTargetCurrency() or setTargetCurrencyColumn().',
        );

        PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->build();
    }

    /**
     * @param string      $sourceCurrencyCode The code of the source currency.
     * @param string      $targetCurrencyCode The code of the target currency.
     * @param string|null $expectedResult     The expected exchange rate, or null if not found.
     */
    #[DataProvider('providerGetExchangeRate')]
    public function testGetExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, ?string $expectedResult): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rates (
                source_currency TEXT NOT NULL,
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?, ?)');

        $statement->execute(['EUR', 'USD', '1.1']);
        $statement->execute(['USD', 'EUR', '0.9']);
        $statement->execute(['USD', 'CAD', '1.2']);

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->build();

        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $actualRate = $provider->getExchangeRate($sourceCurrency, $targetCurrency);

        if ($expectedResult === null) {
            self::assertNull($actualRate);
        } else {
            self::assertBigNumberEquals($expectedResult, $actualRate);
        }
    }

    public static function providerGetExchangeRate(): array
    {
        return [
            ['USD', 'EUR', '0.9'],
            ['EUR', 'USD', '1.1'],
            ['USD', 'CAD', '1.2'],
            ['CAD', 'USD', null],
            ['EUR', 'CAD', null],
        ];
    }

    /**
     * @param string      $sourceCurrencyCode The code of the source currency.
     * @param string      $targetCurrencyCode The code of the target currency.
     * @param string|null $expectedResult     The expected exchange rate, or null if not found.
     */
    #[DataProvider('providerWithFixedSourceCurrency')]
    public function testWithFixedSourceCurrency(string $sourceCurrencyCode, string $targetCurrencyCode, ?string $expectedResult): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rates (
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?)');

        $statement->execute(['USD', '1.1']);
        $statement->execute(['CAD', '1.2']);

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setFixedSourceCurrency('EUR')
            ->setTargetCurrencyColumn('target_currency')
            ->build();

        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $actualRate = $provider->getExchangeRate($sourceCurrency, $targetCurrency);

        if ($expectedResult === null) {
            self::assertNull($actualRate);
        } else {
            self::assertBigNumberEquals($expectedResult, $actualRate);
        }
    }

    public static function providerWithFixedSourceCurrency(): array
    {
        return [
            ['EUR', 'USD', '1.1'],
            ['EUR', 'CAD', '1.2'],
            ['EUR', 'GBP', null],
            ['USD', 'EUR', null],
            ['CAD', 'EUR', null],
        ];
    }

    /**
     * @param string      $sourceCurrencyCode The code of the source currency.
     * @param string      $targetCurrencyCode The code of the target currency.
     * @param string|null $expectedResult     The expected exchange rate, or null if not found.
     */
    #[DataProvider('providerWithFixedTargetCurrency')]
    public function testWithFixedTargetCurrency(string $sourceCurrencyCode, string $targetCurrencyCode, ?string $expectedResult): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rates (
                source_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?)');

        $statement->execute(['USD', '0.9']);
        $statement->execute(['CAD', '0.8']);

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setFixedTargetCurrency('EUR')
            ->build();

        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $actualRate = $provider->getExchangeRate($sourceCurrency, $targetCurrency);

        if ($expectedResult === null) {
            self::assertNull($actualRate);
        } else {
            self::assertBigNumberEquals($expectedResult, $actualRate);
        }
    }

    public function testRejectsNonPositiveExchangeRateFromDatabase(): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rates (
                source_currency TEXT NOT NULL,
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?, ?)');
        $statement->execute(['USD', 'EUR', '0']);

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->build();

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage('Database returned a non-positive exchange rate value.');

        $provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR'));
    }

    public static function providerWithFixedTargetCurrency(): array
    {
        return [
            ['USD', 'EUR', '0.9'],
            ['CAD', 'EUR', '0.8'],
            ['GBP', 'EUR', null],
            ['EUR', 'USD', null],
            ['EUR', 'CAD', null],
        ];
    }

    /**
     * @param string      $sourceCurrencyCode The code of the source currency.
     * @param string      $targetCurrencyCode The code of the target currency.
     * @param string|null $expectedResult     The expected exchange rate, or null if not found.
     */
    #[DataProvider('providerGetExchangeRateByNumericCode')]
    public function testGetExchangeRateByNumericCode(string $sourceCurrencyCode, string $targetCurrencyCode, ?string $expectedResult): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rates (
                source_numeric_currency INTEGER NOT NULL,
                target_numeric_currency INTEGER NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?, ?)');

        $statement->execute([978, 840, '1.1']);
        $statement->execute([840, 978, '0.9']);
        $statement->execute([840, 124, '1.2']);

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->useNumericCurrencyCode()
            ->setSourceCurrencyColumn('source_numeric_currency')
            ->setTargetCurrencyColumn('target_numeric_currency')
            ->build();

        $actualRate = $provider->getExchangeRate(Currency::of($sourceCurrencyCode), Currency::of($targetCurrencyCode));

        if ($expectedResult === null) {
            self::assertNull($actualRate);
        } else {
            self::assertBigNumberEquals($expectedResult, $actualRate);
        }
    }

    public static function providerGetExchangeRateByNumericCode(): array
    {
        return [
            ['USD', 'EUR', '0.9'],
            ['EUR', 'USD', '1.1'],
            ['USD', 'CAD', '1.2'],
            ['CAD', 'USD', null],
            ['EUR', 'CAD', null],
        ];
    }

    public function testNumericCodeSelectorReturnsNullForCurrencyWithoutNumericCode(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (source_numeric_currency INTEGER, target_numeric_currency INTEGER, exchange_rate REAL)');

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->useNumericCurrencyCode()
            ->setSourceCurrencyColumn('source_numeric_currency')
            ->setTargetCurrencyColumn('target_numeric_currency')
            ->build();

        self::assertNull(
            $provider->getExchangeRate(
                new Currency('XBT', null, 'Bitcoin', 8),
                Currency::of('USD'),
            ),
        );
    }

    public function testWithFixedNumericCurrencySelectors(): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rates (
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?)');
        $statement->execute(['1.1']);

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->useNumericCurrencyCode()
            ->setFixedSourceCurrency(978)
            ->setFixedTargetCurrency(840)
            ->build();

        self::assertBigNumberEquals('1.1', $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD')));
        self::assertNull($provider->getExchangeRate(Currency::of('USD'), Currency::of('EUR')));
    }

    public function testBuilderRejectsFixedNumericSelectorWhenNumericModeIsDisabled(): void
    {
        $builder = PdoProvider::builder(new PDO('sqlite::memory:'), 'exchange_rates', 'exchange_rate')
            ->setFixedSourceCurrency(978)
            ->setTargetCurrencyColumn('target_currency');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Fixed source currency is configured as a numeric code, but numeric currency code mode is disabled. Call useNumericCurrencyCode() to enable it.',
        );

        $builder->build();
    }

    public function testBuilderRejectsFixedAlphabeticSelectorWhenNumericModeIsEnabled(): void
    {
        $builder = PdoProvider::builder(new PDO('sqlite::memory:'), 'exchange_rates', 'exchange_rate')
            ->useNumericCurrencyCode()
            ->setFixedSourceCurrency('EUR')
            ->setTargetCurrencyColumn('target_currency');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Fixed source currency is configured as an alphabetic code, but numeric currency code mode is enabled.',
        );

        $builder->build();
    }

    public function testNumericCurrencyCodeModeCannotBeEnabledTwice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Numeric currency code mode is already enabled.');

        PdoProvider::builder(new PDO('sqlite::memory:'), 'exchange_rates', 'exchange_rate')
            ->useNumericCurrencyCode()
            ->useNumericCurrencyCode();
    }

    /**
     * @param string               $sourceCurrencyCode The code of the source currency.
     * @param string               $targetCurrencyCode The code of the target currency.
     * @param array<string, mixed> $dimensions         The dimensions used to resolve rate lookup.
     * @param string|null          $expectedResult     The expected exchange rate, or null if not found.
     */
    #[DataProvider('providerWithDimensions')]
    public function testWithDimensions(string $sourceCurrencyCode, string $targetCurrencyCode, array $dimensions, ?string $expectedResult): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rates (
                year INTEGER NOT NULL,
                month INTEGER NOT NULL,
                source_currency TEXT NOT NULL,
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?, ?, ?, ?)');

        $statement->execute([2017, 8, 'EUR', 'USD', '1.1']);
        $statement->execute([2017, 8, 'EUR', 'CAD', '1.2']);
        $statement->execute([2017, 9, 'EUR', 'USD', '1.15']);
        $statement->execute([2017, 9, 'EUR', 'CAD', '1.25']);

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->bindDimension('year', fn ($year) => new SqlCondition('year = ?', $year))
            ->bindDimension('month', fn ($month) => new SqlCondition('month = ?', $month))
            ->bindDimension(
                'as_of',
                fn (DateTimeInterface $date) => new SqlCondition(
                    'year = ? AND month = ?',
                    (int) $date->format('Y'),
                    (int) $date->format('m'),
                ),
            )
            ->build();

        $sourceCurrency = Currency::of($sourceCurrencyCode);
        $targetCurrency = Currency::of($targetCurrencyCode);

        $actualRate = $provider->getExchangeRate($sourceCurrency, $targetCurrency, $dimensions);

        if ($expectedResult === null) {
            self::assertNull($actualRate);
        } else {
            self::assertBigNumberEquals($expectedResult, $actualRate);
        }
    }

    public static function providerWithDimensions(): array
    {
        return [
            ['EUR', 'USD', ['year' => 2017, 'month' => 8], '1.1'],
            ['EUR', 'CAD', ['year' => 2017, 'month' => 8], '1.2'],
            ['EUR', 'GBP', ['year' => 2017, 'month' => 8], null],
            ['EUR', 'USD', ['year' => 2017, 'month' => 9], '1.15'],
            ['EUR', 'CAD', ['year' => 2017, 'month' => 9], '1.25'],
            ['EUR', 'GBP', ['year' => 2017, 'month' => 9], null],
            ['EUR', 'USD', ['year' => 2017, 'month' => 10], null],
            ['EUR', 'CAD', ['year' => 2017, 'month' => 10], null],
            ['EUR', 'USD', ['as_of' => new DateTimeImmutable('2017-08-10')], '1.1'],
            ['EUR', 'CAD', ['as_of' => new DateTimeImmutable('2017-09-01')], '1.25'],
        ];
    }

    public function testUnknownDimensionReturnsNull(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (source_currency TEXT, target_currency TEXT, exchange_rate REAL)');

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->build();

        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'), ['date' => new DateTimeImmutable('2017-08-01')]);

        self::assertNull($rate);
    }

    public function testSameCurrencyReturnsOne(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (source_currency TEXT, target_currency TEXT, exchange_rate REAL)');

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->build();

        self::assertBigNumberEquals('1', $provider->getExchangeRate(Currency::of('EUR'), Currency::of('EUR')));
    }

    public function testSameCurrencyReturnsOneWithDimensions(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (source_currency TEXT, target_currency TEXT, exchange_rate REAL)');

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->build();

        self::assertBigNumberEquals(
            '1',
            $provider->getExchangeRate(Currency::of('EUR'), Currency::of('EUR'), ['date' => new DateTimeImmutable('2017-08-01')]),
        );
    }

    public function testDimensionCanResolveToNull(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (flag INTEGER, source_currency TEXT, target_currency TEXT, exchange_rate REAL)');
        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?, ?, ?)');
        $statement->execute([1, 'EUR', 'USD', '1.1']);

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->bindDimension('flag', fn (bool $flag) => $flag ? new SqlCondition('flag = 1') : null)
            ->build();

        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'), ['flag' => false]);

        self::assertBigNumberEquals('1.1', $rate);
    }

    public function testDimensionResolverReturningUnsupportedValueTypeThrows(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (source_currency TEXT, target_currency TEXT, exchange_rate REAL)');

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->bindDimension('year', fn () => new SqlCondition('year = ?', new DateTimeImmutable('2025-01-01')))
            ->build();

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage(
            'An exception occurred while resolving SQL condition for dimension: year.',
        );

        $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'), ['year' => 2025]);
    }

    public function testDimensionResolverReturningWrongTypeThrowsException(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (source_currency TEXT, target_currency TEXT, exchange_rate REAL)');

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->bindDimension('year', fn () => 'year = 2025')
            ->build();

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage('Dimension resolver must return Brick\Money\ExchangeRateProvider\Pdo\SqlCondition|null, but returned string for dimension "year".');

        $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'), ['year' => 2025]);
    }

    public function testPrepareExceptionIsWrapped(): void
    {
        $pdo = new PDO('sqlite::memory:');

        $provider = PdoProvider::builder($pdo, 'exchange_rates WHERE', 'exchange_rate')
            ->setFixedSourceCurrency('EUR')
            ->setFixedTargetCurrency('USD')
            ->build();

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage('Failed to prepare exchange rate query due to a PDO exception.');

        $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));
    }

    public function testStaticConditionsWithStaticParameters(): void
    {
        $pdo = new PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rates (
                provider TEXT NOT NULL,
                source_currency TEXT NOT NULL,
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?, ?, ?)');
        $statement->execute(['ECB', 'EUR', 'USD', '1.1']);
        $statement->execute(['INTERNAL', 'EUR', 'USD', '1.12']);

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->setStaticCondition(new SqlCondition('provider = ?', 'ECB'))
            ->build();
        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));

        self::assertBigNumberEquals('1.1', $rate);
    }

    public function testFixedPairWithoutConditionsReadsSingleRowTable(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (exchange_rate REAL NOT NULL)');
        $pdo->prepare('INSERT INTO exchange_rates VALUES (?)')->execute(['1.1']);

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setFixedSourceCurrency('EUR')
            ->setFixedTargetCurrency('USD')
            ->build();
        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));

        self::assertBigNumberEquals('1.1', $rate);
    }

    public function testThrowsWhenQueryReturnsMoreThanOneRow(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (exchange_rate REAL NOT NULL)');
        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?)');
        $statement->execute(['1.1']);
        $statement->execute(['1.2']);

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setFixedSourceCurrency('EUR')
            ->setFixedTargetCurrency('USD')
            ->build();

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage(
            'Exchange rate lookup matched multiple rows. Configure orderBy() to select one row deterministically if that is intended.',
        );

        $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));
    }

    public function testMultipleRowsMessageMentionsOmittedDimensions(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('
            CREATE TABLE exchange_rates (
                year INTEGER NOT NULL,
                month INTEGER NOT NULL,
                source_currency TEXT NOT NULL,
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?, ?, ?, ?)');
        $statement->execute([2017, 8, 'EUR', 'USD', '1.1']);
        $statement->execute([2017, 9, 'EUR', 'USD', '1.15']);

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->bindDimension('year', fn (int $year) => new SqlCondition('year = ?', $year))
            ->bindDimension('month', fn (int $month) => new SqlCondition('month = ?', $month))
            ->build();

        $this->expectException(ExchangeRateProviderException::class);
        $this->expectExceptionMessage(
            'Exchange rate lookup matched multiple rows. Missing dimensions may be required to disambiguate: year, month. Configure orderBy() to select one row deterministically if that is intended.',
        );

        $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));
    }

    public function testOrderByPicksFirstRowDesc(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (priority INTEGER NOT NULL, exchange_rate REAL NOT NULL)');
        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?)');
        $statement->execute([1, '1.1']);
        $statement->execute([2, '1.2']);

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setFixedSourceCurrency('EUR')
            ->setFixedTargetCurrency('USD')
            ->orderBy('priority', 'DESC')
            ->build();
        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));

        self::assertBigNumberEquals('1.2', $rate);
    }

    public function testInvalidOrderDirectionThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Order direction must be ASC or DESC.');

        PdoProvider::builder(new PDO('sqlite::memory:'), 'exchange_rates', 'exchange_rate')
            ->setSourceCurrencyColumn('source_currency')
            ->setTargetCurrencyColumn('target_currency')
            ->orderBy('priority', 'DOWN');
    }

    public function testThenOrderBySupportsMultipleColumns(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->query('CREATE TABLE exchange_rates (priority INTEGER NOT NULL, version INTEGER NOT NULL, exchange_rate REAL NOT NULL)');
        $statement = $pdo->prepare('INSERT INTO exchange_rates VALUES (?, ?, ?)');
        $statement->execute([2, 1, '1.2']);
        $statement->execute([2, 3, '1.23']);
        $statement->execute([2, 2, '1.22']);

        $provider = PdoProvider::builder($pdo, 'exchange_rates', 'exchange_rate')
            ->setFixedSourceCurrency('EUR')
            ->setFixedTargetCurrency('USD')
            ->orderBy('priority', 'DESC')
            ->thenOrderBy('version', 'DESC')
            ->build();
        $rate = $provider->getExchangeRate(Currency::of('EUR'), Currency::of('USD'));

        self::assertBigNumberEquals('1.23', $rate);
    }
}
