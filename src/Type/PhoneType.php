<?php

namespace RzCommon\graphql\Type;

use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;
use GraphQL\Type\Definition\StringType;
use GraphQL\Utils\Utils;

class PhoneType extends StringType
{
    /** @const PATTERN */
    private const PATTERN = '/^\d{12}$/';

    /** @var string */
    public $name = 'Phone';

    /** @var string */
    public $description = 'The `phone` is a string consisting of only 12 digits. Example: "380981234567"';

    /**
     * @const string UKRAINE_CODE
     */
    private const UKRAINE_CODE = '380';

    /**
     * @const int PHONE_LENGTH
     */
    private const PHONE_LENGTH = 12;

    /**
     * @inheritdoc
     */
    public function serialize($value)
    {
        $this->checkItemValue($value);
        $value = parent::serialize($value);

        return $this->formatPhoneFormat($value);
    }

    /**
     * @inheritdoc
     */
    public function parseValue($value)
    {
        $this->checkItemValue($value);
        $value = parent::parseValue($value);

        return $this->formatPhoneFormat($value);
    }

    /**
     * @inheritdoc
     */
    public function parseLiteral($valueNode, ?array $variables = null)
    {
        if ($valueNode instanceof StringValueNode) {
            $this->checkItemValue($valueNode->value);
        }

        $value = parent::parseLiteral($valueNode, $variables);

        return $this->formatPhoneFormat($value);
    }

    /**
     * @mixed value
     *
     * @throws Error
     */
    public function checkItemValue($value)
    {
        if (is_null($value)) {
            return $value;
        }

        $value = $this->formatPhoneFormat($value);

        if (!preg_match(self::PATTERN, $value)) {
            throw new Error("Cannot represent following value as `phone` : " . Utils::printSafeJson($value));
        }

    }

    /**
     * @param string $value
     * @return string
     */
    private function formatPhoneFormat(string $value): string
    {
        $value = preg_replace('/[^\d]/', '', $value);
        if (strlen($value) < static::PHONE_LENGTH) {
            $value = substr(static::UKRAINE_CODE, 0, static::PHONE_LENGTH - strlen($value)) . $value;
        }

        return $value;
    }

}
