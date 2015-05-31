<?php

namespace Brick\Money;

/**
 * Exception thrown when a string cannot be parsed as a Money.
 */
class MoneyParseException extends \RuntimeException
{
    /**
     * @param string $string
     *
     * @return MoneyParseException
     */
    public static function invalidFormat($string)
    {
        return new self(sprintf('"%s" is not a valid Money format.', $string));
    }

    /**
     * @param \Exception $e
     *
     * @return MoneyParseException
     */
    public static function wrap(\Exception $e)
    {
        return new self('Could not parse money.', 0, $e);
    }
}
