<?php

namespace RzCommon\graphql\Type;

use GraphQL\Error\Error;
use GraphQL\Utils\Utils;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Type\Definition\ScalarType;

class UIntType extends ScalarType
{
    /** @var string */
    public $name = 'UnsignedInteger';

    /** @var string */
    public $description = 'The `Unsigned integer` type create only positive integer and zero value';

    /**
     * @inheritdoc
     */
    public function serialize($value): int
    {
        return $this->coerceUInt($value);
    }

    /**
     * @inheritdoc
     */
    public function parseValue($value): int
    {
        return $this->coerceUInt($value);
    }

    /**
     * @inheritdoc
     */
    public function parseLiteral($valueNode, ?array $variables = null): int
    {
        if (!$valueNode instanceof IntValueNode) {
            throw new Error('Query error: Can only parse unsigned integers, got: ' . $valueNode->kind, [$valueNode]);
        }

        return $this->coerceUInt($valueNode->value);
    }

    /**
     * @param mixed $value
     *
     * @return integer
     *
     * @throws Error
     */
    private function coerceUInt($value): int
    {
        $uint = (int)$value;
        if ($uint < 0) {
            throw new Error("Cannot represent following value as unsigned integer: " . Utils::printSafeJson($value));
        }

        return $uint;
    }

}
