<?php
namespace %s;

use %s;
use GraphQL\Type\Definition\ObjectType;

class %s extends ObjectType
{
    public function __construct()
    {
        $config = [
            "fields" => [
%s
            ],
            "description" => "",
            // if you need to add some logic to resolveField,
            // you can call RzCommon\graphql\Endpoint::defaultResolver()($value, $args, $context, ResolveInfo $info)
            // or if you need custom resolver, don't forget to add logging (if you're using it)
        ];
        parent::__construct($config);
    }
    %s
}
