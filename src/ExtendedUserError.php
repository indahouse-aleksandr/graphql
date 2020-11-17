<?php

declare(strict_types=1);

namespace RzCommon\graphql;

use GraphQL\Error\UserError;

class ExtendedUserError extends UserError
{
    /**
     * @var null|string
     */
    private $errorField;

    public function __construct(string $message, int $code = 0, string $errorField = null, \Throwable $throw = null)
    {
        $this->errorField = $errorField;
        parent::__construct($message, $code, $throw);
    }

    /**
     * @return null|string
     */
    public function getErrorField(): ?string
    {
        return $this->errorField;
    }
}
