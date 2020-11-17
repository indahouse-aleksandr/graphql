<?php

namespace RzCommon\graphql\Type;

use GraphQL\Error\Error;
use GraphQL\Utils\Utils;
use GraphQL\Language\AST\IntValueNode;
use GraphQL\Type\Definition\ScalarType;

class PositiveIntType extends ScalarType
{
    /** @var string */
    public $name = 'PositiveInt';

    /** @var string */
    public $description = 'The `PositiveInt` type create only positive integer (not zero)';

    /**
     * Serializes an internal value to include in a response.
     *
     * @param mixed $value
     *
     * @return integer
     *
     * @throws Error
     */
    public function serialize($value): int
    {
        return $this->coerceInt($value);
    }

    /**
     * Parses an externally provided value (query variable) to use as an input
     *
     * @param mixed $value
     *
     * @return integer
     *
     * @throws Error
     */
    public function parseValue($value): int
    {
        return $this->coerceInt($value);
    }

    /**
     * @param mixed $value
     *
     * @return integer
     *
     * @throws Error
     */
    private function coerceInt($value): int
    {
        $int = (int) $value;
        if ($int <= 0) {
            throw new Error("Cannot represent following value as positive integer: " . Utils::printSafeJson($value));
        }
        return $int;
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     *
     * @param Node         $valueNode
     * @param mixed[]|null $variables
     *
     * @return integer
     *
     * @throws Error
     */
    public function parseLiteral($valueNode, ?array $variables = null): int
    {
        if (!$valueNode instanceof IntValueNode) {
            throw new Error('Query error: Can only parse integers, got: ' . $valueNode->kind, [$valueNode]);
        }
        return $this->coerceInt($valueNode->value);
    }
}
