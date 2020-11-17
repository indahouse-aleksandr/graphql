<?php
namespace RzCommon\graphql\Type;

use GraphQL\Type\Definition\EnumType;

class LangType extends EnumType
{
    public const DEFAULT_LANGUAGE = 'ru';
    public const LANGUAGES = ['ru', 'ua'];

    public function __construct()
    {
        $config = [
            'values' => self::LANGUAGES,
            'description' => 'Language type',
        ];
        parent::__construct($config);
    }
}