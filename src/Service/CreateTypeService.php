<?php

namespace RzCommon\graphql\Service;

class CreateTypeService
{
    private $templateDir;

    public function __construct() {
        $this->templateDir = dirname(__FILE__, 2) . '/Template';
    }

    /**
     * Create file GraphQL type
     *
     * @param string $typeName
     * @param array ['name' => string, 'type' => string] $typeFields
     * @param array $params
     *
     * @return array
     */
    public function createTypeFile(string $typeName, array $typeFields, array $params): array
    {
        $registryPath = $params['registry'];
        $typePath = $params['type_folder'];
        $namespace = $params['type_namespace'];
        $className = ucfirst($typeName . "Type");
        $typeFile = $typePath . '/' . $className . ".php";
        file_put_contents($typeFile, $this->getClassStruct($typeFields, $className, $namespace, $registryPath, $params));
        // add property and method to TypeRegistry
        file_put_contents($registryPath, $this->createRegistryMethod($registryPath, $typeName, $className, $namespace));

        return [
            'registryPath' => $registryPath,
            'typePath' => $typePath,
            'namespace' => $namespace,
            'className' => $className,
            'typeFile' => $typeFile,
        ];
    }

    /**
     * Create new php-code for type registry, create methods
     *
     * @param string $registryPath
     * @param string $typeName
     * @param string $className
     * @param string $namespace
     *
     * @return string
     */
    public function createRegistryMethod(string $registryPath, string $typeName, string $className, string $namespace): string
    {
        $tpl = file_get_contents($this->templateDir . "/TypeRegistryMethodTemplate.tpl");
        $registry = file_get_contents($registryPath);
        $registry = preg_replace('/(^namespace [\w\\\]+;\n)/m', '${1}' . PHP_EOL . 'use ' . $namespace . '\\' . $className . ';', $registry);
        $registry = preg_replace('/(^class .*\n\{\n)/m', '${1}    private static $' . $typeName . ';' . PHP_EOL, $registry);
        $registry = preg_replace(
            '/(^\}$)/m',
            sprintf($tpl,
                $className,
                $typeName,
                $className,
                $typeName,
                $typeName,
                $className
            ),
            $registry
        );

        return $registry;
    }

    /**
     * Create new php-code for type registry, create class
     *
     * @param array $fields
     * @param string $className
     * @param string $namespace
     * @param string $registryPath
     * @param array $params
     *
     * @return string
     */
    public function getClassStruct(array $fields, string $className, string $namespace, string $registryPath, array $params): string
    {
        $typeResolveTpl = $params['is_object'] ? 'TypeResolverObjectTemplate.tpl' : 'TypeResolverArrayTemplate.tpl';
        $fieldsString = '';
        $methods = '';
        $exploded = explode('/', $registryPath);
        $registryClass = explode('.', end($exploded))[0];

        $resolverTpl = file_get_contents($this->templateDir . "/" . $typeResolveTpl);
        $typeConfigTpl = file_get_contents($this->templateDir . "/TypeConfigTemplate.tpl");
        foreach ($fields as $field) {

            $fieldsString .= sprintf(
                $typeConfigTpl,
                lcfirst($field['name']),  // field key 'status' => TypeRegistry::string(),
                $registryClass,  // typeRegistry className
                $field['type'],  // typeRegistry methodName (same as type name)
                PHP_EOL
            );

            $methods .= sprintf(
                $resolverTpl,
                $field['name'],         // name of field in PHPDoc
                $field['type'],         // return type in PHPDoc
                ucfirst($field['name']),// method name
                $field['type'],         // return type of method
                $params['is_object'] ? ucfirst($field['name']) : $field['name'] // field
            );
        }

        $tpl = file_get_contents($this->templateDir . "/TypeTemplate.tpl");

        return sprintf(
            $tpl,
            $namespace,    // namespace in php file
            $this->getTypeRegistryNamespace(file_get_contents($registryPath)) . "\\$registryClass", // use registry class
            $className,    // classname in php file
            $fieldsString, // fields in $config in constructor of php file
            $methods       // resolve methods in php file
        );
    }

    /**
     * Get registry type class namespace
     *
     * @return string
     */
    private function getTypeRegistryNamespace(string $typeRegistry): string
    {
        preg_match('/^namespace ([\w\\\]+);/m', $typeRegistry, $matches);
        return $matches[1];
    }
}