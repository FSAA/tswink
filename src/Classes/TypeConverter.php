<?php

namespace TsWink\Classes;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\BigIntType;
use Doctrine\DBAL\Types\BinaryType;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\DateImmutableType;
use Doctrine\DBAL\Types\DateIntervalType;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Types\DateTimeTzImmutableType;
use Doctrine\DBAL\Types\DateTimeTzType;
use Doctrine\DBAL\Types\DateType;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\GuidType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\SimpleArrayType;
use Doctrine\DBAL\Types\SmallFloatType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\TimeImmutableType;
use Doctrine\DBAL\Types\TimeType;
use Doctrine\DBAL\Types\Type;
use TsWink\Exceptions\UnknownTypeException;

class TypeConverter
{
    /** @var Type */
    private $type;

    /**
     * @return "string"|"any"|"number"|"boolean"|"Date"
     */
    public function convert(Column $column): string
    {
        $this->type = $column->getType();

        return match (true) {
            $this->isTypeString() => "string",
            $this->isTypeAny() => "any",
            $this->isTypeNumber() => "number",
            $this->isTypeDecimal() => "number",
            $this->isTypeBoolean() => "boolean",
            $this->isTypeDateTime() => "Date",
            default => throw new UnknownTypeException("Unknown type: {$this->type::lookupName($this->type)}")
        };
    }

    private function isTypeString(): bool
    {
        return $this->type instanceof BinaryType
            || $this->type instanceof GuidType
            || $this->type instanceof StringType
            || $this->type instanceof TextType
            || $this->type instanceof BlobType;
    }

    private function isTypeAny(): bool
    {
        return $this->type instanceof SimpleArrayType
            || $this->type instanceof JsonType;
    }

    private function isTypeNumber(): bool
    {
        return $this->type instanceof BigIntType
            || $this->type instanceof IntegerType
            || $this->type instanceof SmallIntType;
    }

    private function isTypeDecimal(): bool
    {
        return $this->type instanceof DecimalType
            || $this->type instanceof FloatType
            || $this->type instanceof SmallFloatType;
    }

    private function isTypeBoolean(): bool
    {
        return $this->type instanceof BooleanType;
    }

    private function isTypeDateTime(): bool
    {
        return $this->type instanceof DateImmutableType
            || $this->type instanceof DateIntervalType
            || $this->type instanceof DateTimeImmutableType
            || $this->type instanceof DateTimeType
            || $this->type instanceof DateTimeTzImmutableType
            || $this->type instanceof DateTimeTzType
            || $this->type instanceof DateType
            || $this->type instanceof TimeImmutableType
            || $this->type instanceof TimeType;
    }
}
