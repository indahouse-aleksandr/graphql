<?php

namespace RzCommon\graphql\Type;

use GraphQL\Type\Definition\StringType;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Error\Error;

class Varchar extends StringType
{

    const LENGTH = 255;

    /** @var string */
    public $name = 'Varchar';

    /** @var string */
    public $description = 'string limited to 255 characters';

    /**
     * @inheritdoc
     */
    public function serialize($value)
    {
        $this->checkVarchar($value);

        return parent::serialize($value);
    }

    /**
     * @inheritdoc
     */
    public function parseValue($value)
    {
        $this->checkVarchar($value);

        return parent::parseValue($value);
    }

    /**
     * @inheritdoc
     */
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if ($valueNode instanceof StringValueNode) {
            $this->checkVarchar($valueNode->value);
        }

        return parent::parseLiteral($valueNode, $variables);
    }

    /**
     * @mixed value
     *
     * @throws Error
     */
    public function checkVarchar($value)
    {
        if (mb_strlen($value) > self::LENGTH) {
            throw new Error('String cannot be longer than '.self::LENGTH.' characters');
        }
    }

}