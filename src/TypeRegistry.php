<?php
namespace RzCommon\graphql;

use GraphQL\Type\Definition\BooleanType;
use GraphQL\Type\Definition\FloatType;
use GraphQL\Type\Definition\IDType;
use GraphQL\Type\Definition\IntType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use RzCommon\graphql\Type\DatetimeType;
use RzCommon\graphql\Type\EmailType;
use RzCommon\graphql\Type\LangType;
use RzCommon\graphql\Type\PhoneType;
use RzCommon\graphql\Type\PositiveIntType;
use RzCommon\graphql\Type\UIntType;
use RzCommon\graphql\Type\UuidType;
use RzCommon\graphql\Type\Varchar;

class TypeRegistry
{
    private static $email;
    private static $datetime;
    private static $positiveInt;
    private static $uInt;
    private static $lang;
    private static $varchar;
    private static $phone;
    private static $uuid;

    /**
     * Dynamic object type
     *
     * @return ObjectType
     */
    public static function object(array $config): ObjectType
    {
        return new ObjectType($config);
    }

    /**
     * @return \GraphQL\Type\Definition\BooleanType
     */
    public static function boolean(): BooleanType
    {
        return Type::boolean();
    }

    /**
     * @return \GraphQL\Type\Definition\FloatType
     */
    public static function float(): FloatType
    {
        return Type::float();
    }

    /**
     * @return \GraphQL\Type\Definition\IDType
     */
    public static function id(): IDType
    {
        return Type::id();
    }

    /**
     * @return \GraphQL\Type\Definition\IntType
     */
    public static function int(): IntType
    {
        return Type::int();
    }

    /**
     * @return \GraphQL\Type\Definition\StringType
     */
    public static function string(): StringType
    {
        return Type::string();
    }

    /**
     * @param Type $type
     * @return ListOfType
     */
    public static function listOf($type): ListOfType
    {
        return new ListOfType($type);
    }

    /**
     * @param Type $type
     * @return NonNull
     */
    public static function nonNull($type): NonNull
    {
        return new NonNull($type);
    }

    /**
     * @return EmailType
     */
    public static function email(): EmailType
    {
        return self::$email ?: (self::$email = new EmailType());
    }

    /**
     * @return DatetimeType
     */
    public static function datetime(): DatetimeType
    {
        return self::$datetime ?: (self::$datetime = new DatetimeType());
    }

    /**
     * @return PositiveIntType
     */
    public static function positiveInt(): PositiveIntType
    {
        return self::$positiveInt ?: (self::$positiveInt = new PositiveIntType());
    }

    /**
     * @return UIntType
     */
    public static function uInt(): UIntType
    {
        return self::$uInt ?: (self::$uInt = new UIntType());
    }

    /**
     * @return LangType
     */
    public static function lang(): LangType
    {
        return self::$lang ?: (self::$lang = new LangType());
    }

    /**
     * @return \GraphQL\Type\Definition\Varchar
     */
    public static function varchar(): Varchar
    {
        return self::$varchar ?: (self::$varchar = new Varchar());
    }

    /**
     * @return PhoneType
     */
    public static function phone(): PhoneType
    {
        return self::$phone ?: (self::$phone = new PhoneType());
    }

    /**
     * @return UuidType
     */
    public static function uuid(): UuidType
    {
        return self::$uuid ?: (self::$uuid = new UuidType());
    }
}
