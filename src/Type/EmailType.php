<?php

namespace RzCommon\graphql\Type;

use GraphQL\Error\Error;
use GraphQL\Utils\Utils;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\ScalarType;

class EmailType extends ScalarType
{
    /** @var string */
    public $name = 'Email';

    /** @var string */
    public $description = 'The `Email` type validates with PHP function `filter_var` and filter `FILTER_VALIDATE_EMAIL`';

    /**
     * Serializes an internal value to include in a response.
     *
     * @param mixed $value
     *
     * @return string
     *
     * @throws Error
     */
    public function serialize($value): string
    {
        // Assuming internal representation of email is always correct
        return $value;
    }

    /**
     * Parses an externally provided value (query variable) to use as an input
     *
     * @param mixed $value
     *
     * @return string
     *
     * @throws Error
     */
    public function parseValue($value): string
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new Error("Cannot represent following value as email: " . Utils::printSafeJson($value));
        }
        return $value;
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     * 
     * E.g. 
     * {
     *   user(email: "user@example.com") 
     * }
     *
     * @param Node         $valueNode
     * @param mixed[]|null $variables
     *
     * @return string|null
     *
     * @throws Exception
     */
    public function parseLiteral($valueNode, ?array $variables = null): ?string
    {
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings got: ' . $valueNode->kind, [$valueNode]);
        }
        if (!filter_var($valueNode->value, FILTER_VALIDATE_EMAIL)) {
            throw new Error("Not a valid email", [$valueNode]);
        }
        return $valueNode->value;
    }
}
