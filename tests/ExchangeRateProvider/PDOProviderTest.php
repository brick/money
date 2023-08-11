<?php

declare(strict_types=1);

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider\PDOProvider;
use Brick\Money\ExchangeRateProvider\PDOProviderConfiguration;
use Brick\Money\Tests\AbstractTestCase;
use Closure;

/**
 * Tests for class PDOProvider.
 *
 * @requires extension pdo_sqlite
 */
class PDOProviderTest extends AbstractTestCase
{
    /**
     * @dataProvider providerConstructorWithInvalidConfiguration
     *
     * @param Closure(): PDOProviderConfiguration $getConfiguration
     */
    public function testConfigurationConstructorThrows(Closure $getConfiguration, string $exceptionMessage) : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $getConfiguration();
    }

    public static function providerConstructorWithInvalidConfiguration() : array
    {
        return [
            [fn () => new PDOProviderConfiguration(
                tableName: 'exchange_rate',
                exchangeRateColumnName: 'exchange_rate',
                targetCurrencyCode: 'EUR',
            ), 'Invalid configuration: one of $sourceCurrencyCode or $sourceCurrencyColumnName must be set.'],
            [fn () => new PDOProviderConfiguration(
                tableName: 'exchange_rate',
                exchangeRateColumnName: 'exchange_rate',
                sourceCurrencyCode: 'EUR',
                sourceCurrencyColumnName: 'source_currency_code',
                targetCurrencyCode: 'EUR',
            ), 'Invalid configuration: $sourceCurrencyCode and $sourceCurrencyColumnName cannot be both set.'],
            [fn () => new PDOProviderConfiguration(
                tableName: 'exchange_rate',
                exchangeRateColumnName: 'exchange_rate',
                sourceCurrencyCode: 'EUR',
            ), 'Invalid configuration: one of $targetCurrencyCode or $targetCurrencyColumnName must be set.'],
            [fn () => new PDOProviderConfiguration(
                tableName: 'exchange_rate',
                exchangeRateColumnName: 'exchange_rate',
                sourceCurrencyCode: 'EUR',
                targetCurrencyCode: 'EUR',
                targetCurrencyColumnName: 'target_currency_code',
            ), 'Invalid configuration: $targetCurrencyCode and $targetCurrencyColumnName cannot be both set.'],
            [fn () => new PDOProviderConfiguration(
                tableName: 'exchange_rate',
                exchangeRateColumnName: 'exchange_rate',
                sourceCurrencyCode: 'EUR',
                targetCurrencyCode: 'EUR',
            ), 'Invalid configuration: $sourceCurrencyCode and $targetCurrencyCode cannot be both set.'],
        ];
    }

    /**
     * @dataProvider providerGetExchangeRate
     *
     * @param string       $sourceCurrencyCode The code of the source currency.
     * @param string       $targetCurrencyCode The code of the target currency.
     * @param float|string $expectedResult     The expected exchange rate, or an exception class if expected.
     */
    public function testGetExchangeRate(string $sourceCurrencyCode, string $targetCurrencyCode, float|string $expectedResult) : void
    {
        $pdo = new \PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rate (
                source_currency TEXT NOT NULL,
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rate VALUES (?, ?, ?)');

        $statement->execute(['EUR', 'USD', 1.1]);
        $statement->execute(['USD', 'EUR', 0.9]);
        $statement->execute(['USD', 'CAD', 1.2]);

        $configuration = new PDOProviderConfiguration(
            tableName: 'exchange_rate',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyColumnName: 'source_currency',
            targetCurrencyColumnName: 'target_currency',
        );

        $provider = new PDOProvider($pdo, $configuration);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualRate = $provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

        if (! $this->isExceptionClass($expectedResult)) {
            self::assertEquals($expectedResult, $actualRate);
        }
    }

    public static function providerGetExchangeRate() : array
    {
        return [
            ['USD', 'EUR', 0.9],
            ['EUR', 'USD', 1.1],
            ['USD', 'CAD', 1.2],
            ['CAD', 'USD', CurrencyConversionException::class],
            ['EUR', 'CAD', CurrencyConversionException::class],
        ];
    }

    /**
     * @dataProvider providerWithFixedSourceCurrency
     *
     * @param string       $sourceCurrencyCode The code of the source currency.
     * @param string       $targetCurrencyCode The code of the target currency.
     * @param float|string $expectedResult     The expected exchange rate, or an exception class if expected.
     */
    public function testWithFixedSourceCurrency(string $sourceCurrencyCode, string $targetCurrencyCode, float|string $expectedResult) : void
    {
        $pdo = new \PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rate (
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rate VALUES (?, ?)');

        $statement->execute(['USD', 1.1]);
        $statement->execute(['CAD', 1.2]);

        $configuration = new PDOProviderConfiguration(
            tableName: 'exchange_rate',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyCode: 'EUR',
            targetCurrencyColumnName: 'target_currency',
        );

        $provider = new PDOProvider($pdo, $configuration);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualRate = $provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

        if (! $this->isExceptionClass($expectedResult)) {
            self::assertEquals($expectedResult, $actualRate);
        }
    }

    public static function providerWithFixedSourceCurrency() : array
    {
        return [
            ['EUR', 'USD', 1.1],
            ['EUR', 'CAD', 1.2],
            ['EUR', 'GBP', CurrencyConversionException::class],
            ['USD', 'EUR', CurrencyConversionException::class],
            ['CAD', 'EUR', CurrencyConversionException::class],
        ];
    }

    /**
     * @dataProvider providerWithFixedTargetCurrency
     *
     * @param string       $sourceCurrencyCode The code of the source currency.
     * @param string       $targetCurrencyCode The code of the target currency.
     * @param float|string $expectedResult     The expected exchange rate, or an exception class if expected.
     */
    public function testWithFixedTargetCurrency(string $sourceCurrencyCode, string $targetCurrencyCode, float|string $expectedResult) : void
    {
        $pdo = new \PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rate (
                source_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rate VALUES (?, ?)');

        $statement->execute(['USD', 0.9]);
        $statement->execute(['CAD', 0.8]);

        $configuration = new PDOProviderConfiguration(
            tableName: 'exchange_rate',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyColumnName: 'source_currency',
            targetCurrencyCode: 'EUR',
        );

        $provider = new PDOProvider($pdo, $configuration);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualRate = $provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

        if (! $this->isExceptionClass($expectedResult)) {
            self::assertEquals($expectedResult, $actualRate);
        }
    }

    public static function providerWithFixedTargetCurrency() : array
    {
        return [
            ['USD', 'EUR', 0.9],
            ['CAD', 'EUR', 0.8],
            ['GBP', 'EUR', CurrencyConversionException::class],
            ['EUR', 'USD', CurrencyConversionException::class],
            ['EUR', 'CAD', CurrencyConversionException::class],
        ];
    }

    /**
     * @dataProvider providerWithParameters
     *
     * @param string       $sourceCurrencyCode The code of the source currency.
     * @param string       $targetCurrencyCode The code of the target currency.
     * @param array        $parameters         The parameters to resolve the extra query placeholders.
     * @param float|string $expectedResult     The expected exchange rate, or an exception class if expected.
     */
    public function testWithParameters(string $sourceCurrencyCode, string $targetCurrencyCode, array $parameters, float|string $expectedResult) : void
    {
        $pdo = new \PDO('sqlite::memory:');

        $pdo->query('
            CREATE TABLE exchange_rate (
                year INTEGER NOT NULL,
                month INTEGER NOT NULL,
                source_currency TEXT NOT NULL,
                target_currency TEXT NOT NULL,
                exchange_rate REAL NOT NULL
            )
        ');

        $statement = $pdo->prepare('INSERT INTO exchange_rate VALUES (?, ?, ?, ?, ?)');

        $statement->execute([2017, 8, 'EUR', 'USD', 1.1]);
        $statement->execute([2017, 8, 'EUR', 'CAD', 1.2]);
        $statement->execute([2017, 9, 'EUR', 'USD', 1.15]);
        $statement->execute([2017, 9, 'EUR', 'CAD', 1.25]);

        $configuration = new PDOProviderConfiguration(
            tableName: 'exchange_rate',
            exchangeRateColumnName: 'exchange_rate',
            sourceCurrencyColumnName: 'source_currency',
            targetCurrencyColumnName: 'target_currency',
            whereConditions: 'year = ? AND month = ?',
        );

        $provider = new PDOProvider($pdo, $configuration);
        $provider->setParameters(...$parameters);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualRate = $provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

        if (! $this->isExceptionClass($expectedResult)) {
            self::assertEquals($expectedResult, $actualRate);
        }
    }

    public static function providerWithParameters() : array
    {
        return [
            ['EUR', 'USD', [2017, 8], 1.1],
            ['EUR', 'CAD', [2017, 8], 1.2],
            ['EUR', 'GBP', [2017, 8], CurrencyConversionException::class],
            ['EUR', 'USD', [2017, 9], 1.15],
            ['EUR', 'CAD', [2017, 9], 1.25],
            ['EUR', 'GBP', [2017, 9], CurrencyConversionException::class],
            ['EUR', 'USD', [2017, 10], CurrencyConversionException::class],
            ['EUR', 'CAD', [2017, 10], CurrencyConversionException::class],
        ];
    }
}
