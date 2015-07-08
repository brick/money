<?php

namespace Brick\Money\CurrencyConversion\ExchangeRateProvider;

use Brick\Money\Currency;
use Brick\Money\CurrencyConversion\ExchangeRateProvider;
use Brick\Money\Exception\CurrencyConversionException;

/**
 * Reads exchange rates from a PDO database connection.
 */
class PDOProvider implements ExchangeRateProvider
{
    const TABLE_NAME                  = 'TABLE_NAME';
    const EXCHANGE_RATE_COLUMN_NAME   = 'EXCHANGE_RATE_COLUMN_NAME';
    const SOURCE_CURRENCY_COLUMN_NAME = 'SOURCE_CURRENCY_COLUMN_NAME';
    const TARGET_CURRENCY_COLUMN_NAME = 'TARGET_CURRENCY_COLUMN_NAME';

    /**
     * @var \PDOStatement
     */
    private $statement;

    /**
     * @param \PDO  $pdo
     * @param array $configuration
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(\PDO $pdo, array $configuration)
    {
        $keys = [
            self::TABLE_NAME,
            self::EXCHANGE_RATE_COLUMN_NAME,
            self::SOURCE_CURRENCY_COLUMN_NAME,
            self::TARGET_CURRENCY_COLUMN_NAME
        ];

        foreach ($keys as $key) {
            if (! isset($configuration[$key])) {
                throw new \InvalidArgumentException($key . ' is not configured.');
            }
        }

        $query = sprintf(
            'SELECT %s FROM %s WHERE %s = ? AND %s = ?',
            $configuration[self::EXCHANGE_RATE_COLUMN_NAME],
            $configuration[self::TABLE_NAME],
            $configuration[self::SOURCE_CURRENCY_COLUMN_NAME],
            $configuration[self::TARGET_CURRENCY_COLUMN_NAME]
        );

        $this->statement = $pdo->prepare($query);
    }

    /**
     * {@inheritdoc}
     */
    public function getExchangeRate(Currency $source, Currency $target)
    {
        $this->statement->execute([
            $source->getCode(),
            $target->getCode()
        ]);

        $exchangeRate = $this->statement->fetchColumn();

        if ($exchangeRate === false) {
            throw CurrencyConversionException::exchangeRateNotAvailable($source, $target);
        }

        return $exchangeRate;
    }
}
