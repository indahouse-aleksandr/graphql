<?php
namespace RzCommon\graphql;

use GraphQL\Error\{
    Debug, Error, FormattedError
};
use GraphQL\Error\UserError;
use GraphQL\Executor\Executor;
use GraphQL\GraphQL;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;
use GraphQL\Validator\DocumentValidator;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Monolog\Logger;

class Endpoint
{
    protected $schema = [];
    protected $isDebugEnabled = false;

    protected static $logAll = true;
    protected static $defaultRoleChecker = false;
    protected static $queryId;
    protected static $logger;
    protected static $path = []; // path needed for RBAC check

    protected $maxQueryComplexity = 100;
    protected $maxQueryDepth = 10;

    /**
     * @var string
     */
    private static $foreignServiceUser = null;

    public function __construct(bool $isDebugEnabled = false)
    {
        self::$logger = new Logger('graphql');
        // use uniqid with more entropy for uniqueness
        self::$queryId = uniqid("", true);
        $this->isDebugEnabled = $isDebugEnabled;
    }

    /**
     * @param string|null $user
     */
    public static function setForeignServiceUser(?string $user): void
    {
        self::$foreignServiceUser = $user;
    }

    /**
     * Execute query
     *
     * @return array
     */
    public function execute($appContext = null): array
    {
        $startTime = microtime(true);
        $debug = $this->isDebugEnabled ? Debug::INCLUDE_DEBUG_MESSAGE | Debug::INCLUDE_TRACE : false;
        if (!$this->schema) {
            throw new \Exception('You have not set main schema types.');
        }
        $rawData = $this->getQuery();
        $query = $rawData['query'] ?? false;

        // log before query
        self::$logger->info(self::getLogMessage([
            'id' => self::$queryId,
            'query' => $query,
            'action' => 'start',
        ]));

        if (!$query) {
            throw new \Exception("You must set query");
        }
        $this->setRules();

        $schema = new Schema($this->schema);
        $response = GraphQL::executeQuery(
            $schema,
            $query,
            null,
            $appContext,
            (array) ($rawData['variables'] ?? null),
            null,
            self::defaultResolver()
        )->setErrorFormatter(function(Error $error) {
            $formatted = FormattedError::createFromException($error);
            $original = $error->getPrevious() instanceof \Exception ? $error->getPrevious() : $error;

            self::$logger->error(self::getLogMessage([
                'id' => self::$queryId,
                'error' => "Message: " . $original->getMessage()
                    . "; File: " . $original->getFile()
                    . "; Line: " . $original->getLine()
                    . "; Code: " . $original->getCode(),
                'trace' => $original->getTraceAsString(),
                'action' => 'error',
            ]));

            $formatted = $this->setAdvancedFields($original, $formatted);

            return $formatted;
        });

        $result = $response->toArray($debug);

        // log after query
        self::$logger->info(self::getLogMessage([
            'id' => self::$queryId,
            'query' => $query,
            'variables' => (array) ($rawData['variables'] ?? null),
            'execute_time' => microtime(true) - $startTime,
            'result' => json_encode($result),
            'action' => 'end',
        ]));

        return $result;
    }

    protected function setAdvancedFields($exception, array $formatted): array
    {
        if (method_exists($exception, 'getCode') && $exception->getCode()) {
            $formatted['extensions']['code'] = $exception->getCode();
        }

        if ($exception instanceof ExtendedUserError) {
            $formatted['extensions']['field'] = $exception->getErrorField();
        }

        return $formatted;
    }

    /**
     * @param  array $params
     * @return string
     */
    public static function getLogMessage(array $params): string
    {
        $rUuid = $_SERVER['HTTP_R_UUID'] ?? null;

        if (!empty($rUuid)) {
            $params['r-uuid'] = $rUuid;
        }

        $params['remote_addr'] = $_SERVER['REMOTE_ADDR'] ?? null;
        $params['auth_user'] = $_SERVER['PHP_AUTH_USER'] ?? $_SERVER['REMOTE_USER'] ?? null;
        if (self::$foreignServiceUser) {
            $params['foreign_service_user'] = self::$foreignServiceUser;
        }

        return json_encode($params);
    }

    /**
     * Get default resolver function
     *
     * @return Closure
     */
    public static function defaultResolver(): \Closure
    {
        return function($value, $args, $context, ResolveInfo $info) {
            // check is user allowed to see this node
            if (!self::isAllowed($context, $info)) {
                throw new UserError('Permission denied', 401);
            }
            $method = 'resolve' . ucfirst($info->fieldName);
            $typeName = reset($info->path);
            if (method_exists($info->parentType, $method)) {
                $startTime = microtime(true);
                $result = $info->parentType->{$method}($value, $args, $context, $info);
                if (self::$logAll) {
                    self::$logger->info(self::getLogMessage([
                        'id' => self::$queryId,
                        'query' => $typeName."->".$method,
                        'execute_time' => microtime(true) - $startTime,
                        'result' => json_encode($result),
                    ]));
                }
                return $result;
            }
            return Executor::defaultFieldResolver($value, $args, $context, $info);
        };
    }

    /**
     * Check is user has needed role
     *
     * @param $context must have method "getUserRoles"
     * @param $info ResolveInfo
     *
     * @return boolean
     */
    public static function isAllowed($context, ResolveInfo $info): bool
    {
        // if this check is enabled
        if (!self::$defaultRoleChecker) {
            return true;
        }
        // we need to get user roles
        if (!method_exists($context, 'getUserRoles')) {
            throw new \Exception('Please create method "getUserRoles" in your context class, which returns user roles');
        }
        // construct full path by nodes + operation name
        // e.g. query.user.programLoyalty
        self::changePath($info);
        $fullPath = $info->operation->operation . '.' . implode('.', self::$path) . (!empty(self::$path) ? '.' : '');
        // make double check, 'cause there can be case when we have specific rule and cannot get to it.
        // e.g. user role is mutation.user.create (can only create user), we access node mutation.user and can't pass it
        foreach ($context->getUserRoles() as $role) {
            if (stripos($fullPath, $role) !== false || stripos($role, $fullPath) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Construct own path according to webonyx lib path
     *
     * @return void
     */
    protected static function changePath(ResolveInfo $info): void
    {
        // get pure level without array keys
        $level = count(array_filter($info->path, function($val) {
            return is_string($val);
        }));
        self::$path = array_slice(self::$path, 0, $level - 1);
        if ($level != count(self::$path)) {
            // we save only nodes which has children
            $node = end($info->fieldNodes);
            if (!is_null($node->selectionSet)) {
                self::$path[] = $info->fieldName;
            }
        }
        // to prevent from losing top lvl nodes
        if (strtolower($info->parentType->name) == $info->operation->operation) {
            self::$path = [$info->fieldName];
        }
    }

    /**
     * Set validation rules
     *
     * @return void
     */
    protected function setRules(): void
    {
        DocumentValidator::addRule(new QueryComplexity($this->maxQueryComplexity));
        DocumentValidator::addRule(new QueryDepth($this->maxQueryDepth));
    }

    /**
     * Set maximum query complexity
     *
     * @return self
     */
    public function setMaxQueryComplexity(int $maxQueryComplexity): self
    {
        $this->maxQueryComplexity = $maxQueryComplexity;

        return $this;
    }

    /**
     * Set maximum query depth
     *
     * @return self
     */
    public function setMaxQueryDepth(int $maxQueryDepth): self
    {
        $this->maxQueryDepth = $maxQueryDepth;

        return $this;
    }

    /**
     * Get logger
     *
     * @return Logger
     */
    public static function getLogger(): Logger
    {
        return self::$logger;
    }

    /**
     * Set logger
     *
     * @return void
     */
    public static function setLogger(string $name): void
    {
        self::$logger = new Logger($name);
    }

    /**
     * Set logAll property.
     * If set to false, then we log only start query, final result and errors.
     * If set to true, we log every resolver.
     *
     * @return void
     */
    public static function setLogAll(bool $bool): void
    {
        self::$logAll = $bool;
    }

    /**
     * Enable default user role check on every resolve in defaultResolver()
     *
     * @return void
     */
    public static function enableDefaultRoleChecker(bool $bool): void
    {
        self::$defaultRoleChecker = $bool;
    }

    /**
     * Set query in schema
     *
     * @return self
     */
    public function setQuerySchema($queryObject): self
    {
        $this->schema['query'] = $queryObject;

        return $this;
    }

    /**
     * Set mutation in schema
     *
     * @return self
     */
    public function setMutationSchema($mutationObject): self
    {
        $this->schema['mutation'] = $mutationObject;

        return $this;
    }

    /**
     * Get query string from php://input and not from $_POST
     * explanation here: https://stackoverflow.com/a/8893792
     *
     * @return array
     */
    protected function getQuery(): array
    {
        if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $raw = file_get_contents('php://input') ?: '';
            $data = json_decode($raw, true) ?: [];
        } else {
            $data = $_REQUEST;
        }
        return $data;
    }

    /**
     * Get query ID
     *
     * @return string
     */
    public static function getQueryId(): string
    {
        return self::$queryId;
    }
}
