<?php

namespace RzCommon\graphql\Type;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\StringType;
use GraphQL\Utils\Utils;

/**
 * Class UuidType
 *
 * @package RzCommon\graphql\Type
 */
class UuidType extends StringType
{
    /**
     * @var string
     */
    private $pattern = '/^[0-9a-f]{8}(\-?[0-9a-f]{4}){3}\-?[0-9a-f]{12}$/i';

    /**
     * @var string 
     */
    public $name = 'Uuid';

    /**
     * @var string 
     */
    public $description = 'The `Uuid` type validate by regex';

    /**
     * @inheritdoc
     */
    public function serialize($value)
    {
        $this->checkByRegexp($value);

        return parent::serialize($value);
    }

    /**
     * @inheritdoc
     */
    public function parseValue($value)
    {
        $value = mb_strtolower(trim($value));
        $this->checkByRegexp($value);

        return parent::parseValue($value);
    }

    /**
     * @inheritdoc
     */
    public function parseLiteral($valueNode, ?array $variables = null): string
    {
        if ($valueNode instanceof StringValueNode) {
            $valueNode->value = mb_strtolower(trim($valueNode->value));
            $this->checkByRegexp($valueNode->value);
        }

        return parent::parseLiteral($valueNode, $variables);
    }

    private function checkByRegexp($value)
    {
        if (!preg_match($this->pattern, $value)) {
            throw new Error('This is not a valid UUID');
        }
    }
}

