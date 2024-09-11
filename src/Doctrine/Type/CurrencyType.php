<?php

declare(strict_types=1);

namespace Brick\Money\Doctrine\Type;

use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

class CurrencyType extends Type
{
    public const NAME = 'currency';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null || $value instanceof Currency) {
            return $value;
        }

        try {
            return Currency::of($value);
        } catch (UnknownCurrencyException $e) {
            throw ValueNotConvertible::new($value, Currency::class);
        }
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Currency) {
            return $value->getCurrencyCode();
        }

        throw ValueNotConvertible::new($value, 'string');
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
