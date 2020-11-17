<?php

namespace RzCommon\graphql\Service;

use Exception;

class DBService
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * @var array
     */
    private $dbTypes = [
        'int'      => ['int2', 'int8', 'int4', 'float8'],
        'string'   => ['varchar', 'text'],
        'boolean'  => ['bool'],
        'datetime' => ['timestamp']
    ];

    /**
     * Get object for work with database
     *
     * @return \PDO
     */
    public function DB(): \PDO
    {
        return $this->db;
    }

    /**
     * Make connection to database
     *
     * @param string $dsn
     *
     * @throws \PDOException
     * @return void
     */
    public function makeConnect(string $dsn): void
    {
        $connectInfo = $this->parseDSN($dsn);
        $dsn = 'pgsql:host=' . $connectInfo['host'] . ';dbname=' . $connectInfo['database'] . ';port=' . $connectInfo['port'] . ';';
        $user = $connectInfo['user'];
        $password = $connectInfo['password'];
        $this->db = new \PDO($dsn, $user, $password);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Parse dns, return array connection info
     *
     * @param string $dsn
     *
     * @return array
     */
    public function parseDSN(string $dsn): array
    {
        $result = [
            'user' => '',
            'password' => '',
            'host' => '',
            'port' => 0,
            'database' => ''
        ];
        $regExp = '/^(?P<db>\w+):\/\/(?P<user>\w+)(:(?P<password>\w+))?@(?P<host>[.\w]+)(:(?P<port>\d+))?\/(?P<database>\w+)$/mi';
        $matches = [];
        preg_match($regExp, $dsn, $matches);
        foreach ($result as $key => $value) {
            if (!empty($matches[$key])) {
                $result[$key] = $matches[$key];
            }
        }

        return $result;
    }

    /**
     * Get information about GraphQL type fields from database table
     *
     * @param string $table
     *
     * @return array
     */
    public function getFieldsFromDatabaseTable(string $table): array
    {
        $fields = [];
        $tableInfo = $this->DB()->query("SELECT * FROM $table LIMIT 1");
        if(!$tableInfo){
            throw new Exception("There is no information in table in database");
        }

        for ($i = 0; $i < $tableInfo->columnCount(); $i++) {
            $metadata = $tableInfo->getColumnMeta($i);
            $fieldName = $metadata['name'];
            $fieldType = $metadata['native_type'];
            foreach ($this->dbTypes as $type => $variants) {
                if (in_array($fieldType, $variants)) {
                    $fieldType = $type;
                    break;
                }
            }
            $fields[] = ['name' => $this->underscoreToCamelCase($fieldName), 'type' => $fieldType];
        }

        return $fields;
    }

    /**
     * replace style from underscore to camelCase
     *
     * @param string $field
     *
     * @return string
     */
    public function underscoreToCamelCase(string $field): string
    {
        return str_replace('_', '', ucwords($field, '_'));
    }
}