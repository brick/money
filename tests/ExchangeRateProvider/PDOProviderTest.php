<?php

namespace Brick\Money\Tests\ExchangeRateProvider;

use Brick\Money\Exception\CurrencyConversionException;
use Brick\Money\ExchangeRateProvider\PDOProvider;
use Brick\Money\ExchangeRateProvider\PDOProviderConfiguration;
use Brick\Money\Tests\AbstractTestCase;

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
     * @param PDOProviderConfiguration $configuration
     * @param string                   $exceptionMessage
     */
    public function testConstructorWithInvalidConfiguration(PDOProviderConfiguration $configuration, $exceptionMessage)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $pdo = new \PDO('sqlite::memory:');

        new PDOProvider($pdo, $configuration);
    }

    /**
     * @return array
     */
    public function providerConstructorWithInvalidConfiguration()
    {
        $noTableName = new PDOProviderConfiguration();

        $noExchangeRateColumnName = clone $noTableName;
        $noExchangeRateColumnName->tableName = 'exchange_rate';

        $noSourceCurrency = clone $noExchangeRateColumnName;
        $noSourceCurrency->exchangeRateColumnName = 'exchange_rate';

        $noTargetCurrency = clone $noSourceCurrency;
        $noTargetCurrency->sourceCurrencyCode = 'EUR';

        $fixedSourceAndTargetCurrency = clone $noTargetCurrency;
        $fixedSourceAndTargetCurrency->targetCurrencyCode = 'USD';

        return [
            [$noTableName, '$tableName'],
            [$noExchangeRateColumnName, '$exchangeRateColumnName'],
            [$noSourceCurrency, '$sourceCurrencyCode or $sourceCurrencyColumnName'],
            [$noTargetCurrency, '$targetCurrencyCode or $targetCurrencyColumnName'],
            [$fixedSourceAndTargetCurrency, '$sourceCurrencyCode and $targetCurrencyCode']
        ];
    }

    /**
     * @dataProvider providerGetExchangeRate
     *
     * @param string       $sourceCurrencyCode The code of the source currency.
     * @param string       $targetCurrencyCode The code of the target currency.
     * @param float|string $expectedResult     The expected exchange rate, or an exception class if expected.
     */
    public function testGetExchangeRate($sourceCurrencyCode, $targetCurrencyCode, $expectedResult)
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

        $configuration = new PDOProviderConfiguration();

        $configuration->tableName                = 'exchange_rate';
        $configuration->sourceCurrencyColumnName = 'source_currency';
        $configuration->targetCurrencyColumnName = 'target_currency';
        $configuration->exchangeRateColumnName   = 'exchange_rate';

        $provider = new PDOProvider($pdo, $configuration);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualRate = $provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertEquals($expectedResult, $actualRate);
        }
    }

    /**
     * @return array
     */
    public function providerGetExchangeRate()
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
    public function testWithFixedSourceCurrency($sourceCurrencyCode, $targetCurrencyCode, $expectedResult)
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

        $configuration = new PDOProviderConfiguration();

        $configuration->tableName                = 'exchange_rate';
        $configuration->sourceCurrencyCode       = 'EUR';
        $configuration->targetCurrencyColumnName = 'target_currency';
        $configuration->exchangeRateColumnName   = 'exchange_rate';

        $provider = new PDOProvider($pdo, $configuration);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualRate = $provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertEquals($expectedResult, $actualRate);
        }
    }

    /**
     * @return array
     */
    public function providerWithFixedSourceCurrency()
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
    public function testWithFixedTargetCurrency($sourceCurrencyCode, $targetCurrencyCode, $expectedResult)
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

        $configuration = new PDOProviderConfiguration();

        $configuration->tableName                = 'exchange_rate';
        $configuration->sourceCurrencyColumnName = 'source_currency';
        $configuration->targetCurrencyCode       = 'EUR';
        $configuration->exchangeRateColumnName   = 'exchange_rate';

        $provider = new PDOProvider($pdo, $configuration);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualRate = $provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertEquals($expectedResult, $actualRate);
        }
    }

    /**
     * @return array
     */
    public function providerWithFixedTargetCurrency()
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
    public function testWithParameters($sourceCurrencyCode, $targetCurrencyCode, $parameters, $expectedResult)
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

        $configuration = new PDOProviderConfiguration();

        $configuration->tableName                = 'exchange_rate';
        $configuration->sourceCurrencyColumnName = 'source_currency';
        $configuration->targetCurrencyColumnName = 'target_currency';
        $configuration->exchangeRateColumnName   = 'exchange_rate';
        $configuration->whereConditions          = 'year = ? AND month = ?';

        $provider = new PDOProvider($pdo, $configuration);
        $provider->setParameters(...$parameters);

        if ($this->isExceptionClass($expectedResult)) {
            $this->expectException($expectedResult);
        }

        $actualRate = $provider->getExchangeRate($sourceCurrencyCode, $targetCurrencyCode);

        if (! $this->isExceptionClass($expectedResult)) {
            $this->assertEquals($expectedResult, $actualRate);
        }
    }

    /**
     * @return array
     */
    public function providerWithParameters()
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
