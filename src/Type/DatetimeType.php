<?php

namespace RzCommon\graphql\Type;

use \DateTime;
use GraphQL\Error\Error;
use GraphQL\Utils\Utils;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

class DatetimeType extends ScalarType
{
    public const DATE_MIN = '1970-01-01 00:00:00';

    /** @var string */
    public $name = 'Datetime';

    /** @var string */
    public $description = 'The `Datetime` type create PHP class DateTime and validates it';

    /**
     * Serializes an internal value to include in a response.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function serialize($value)
    {
        return $value;
    }

    /**
     * Parses an externally provided value (query variable) to use as an input
     *
     * @param mixed $value
     *
     * @return DateTime
     *
     * @throws Error
     */
    public function parseValue($value): DateTime
    {
        $this->checkItemValue($value);
        $datetime = date_create($value);

        return $datetime;
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     *
     * @param Node         $valueNode
     * @param mixed[]|null $variables
     *
     * @return DateTime
     *
     * @throws Exception
     */
    public function parseLiteral($valueNode, ?array $variables = null): DateTime
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, [$valueNode]);
        }
        $this->checkItemValue($valueNode->value);
        $datetime = date_create($valueNode->value);

        return $datetime;
    }

    /**
     * @mixed value
     *
     * @throws Error
     */
    private function checkItemValue($value)
    {
        $dateMin = date_create(self::DATE_MIN);
        $date = date_create($value);
        $isCorrect = $date ? checkdate($date->format("m"), $date->format("d"), $date->format("Y")) : false;

        if (is_null($value) || $value === '' || !$date || !$isCorrect) {
            throw new Error("The parsed date was invalid", [$value]);
        }
        if ($date < $dateMin) {
            throw new Error("Date must be no earlier than " . self::DATE_MIN, [$value]);
        }
    }
}
