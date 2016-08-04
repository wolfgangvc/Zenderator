<?php
namespace Zenderator;

use Camel\CaseTransformer;
use Camel\Format;
use Segura\AppCore\App;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Response;
use Slim\Http\Uri;
use Thru\Inflection\Inflect;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Adapter as DbAdaptor;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Metadata\Object\ConstraintObject;

class Zenderator
{
    private $rootOfApp;
    private $config;
    private $composer; // @todo rename $composerConfig
    private $namespace;
    /** @var \Twig_Loader_Filesystem */
    private $loader;
    /** @var \Twig_Environment */
    private $twig;
    /** @var Adapter */
    private $adapter;
    /** @var Metadata */
    private $metadata;

    private $ignoredTables;

    private $transSnake2Studly;
    private $transStudly2Camel;
    private $transCamel2Studly;
    private $transSnake2Camel;
    private $transSnake2Spinal;
    private $transCamel2Snake;

    public function __construct($rootOfApp, $databaseConfiguration)
    {
        $this->rootOfApp = $rootOfApp;
        $this->setUp($databaseConfiguration);
    }

    public function makeZenderator()
    {
        $models = $this->makeModelSchemas();
        $this->makeCoreFiles($models);
        $this->cleanCode();
    }

    public function makeSDK($outputPath = APP_ROOT)
    {
        $models = $this->makeModelSchemas();
        $this->makeSDKFiles($models, $outputPath);
        $this->cleanCode();
    }

    private function setUp($databaseConfiguration)
    {
        if (!file_exists($this->rootOfApp . "/zenderator.yml")) {
            die("Missing Zenderator config /zenderator.yml\nThere is an example in /vendor/bin/segura/zenderator/zenderator.example.yml\n\n");
        }
        $this->config = file_get_contents($this->rootOfApp . "/zenderator.yml");
        $this->config = \Symfony\Component\Yaml\Yaml::parse($this->config);

        $this->composer  = json_decode(file_get_contents($this->rootOfApp . "/composer.json"));
        $namespaces      = array_keys((array) $this->composer->autoload->{'psr-4'});
        $this->namespace = rtrim($namespaces[0], '\\');

        $this->loader = new \Twig_Loader_Filesystem(__DIR__ . "/../generator/templates");
        $this->twig   = new \Twig_Environment($this->loader);

        $this->twig->addExtension(
            new \Segura\AppCore\Twig\Extensions\ArrayUniqueTwigExtension()
        );

        $this->ignoredTables = [
            'tbl_migration',
        ];

        $this->transSnake2Studly = new CaseTransformer(new Format\SnakeCase(), new Format\StudlyCaps());
        $this->transStudly2Camel = new CaseTransformer(new Format\StudlyCaps(), new Format\CamelCase());
        $this->transCamel2Studly = new CaseTransformer(new Format\CamelCase(), new Format\StudlyCaps());
        $this->transSnake2Camel  = new CaseTransformer(new Format\SnakeCase(), new Format\CamelCase());
        $this->transSnake2Spinal = new CaseTransformer(new Format\SnakeCase(), new Format\SpinalCase());
        $this->transCamel2Snake  = new CaseTransformer(new Format\CamelCase(), new Format\SnakeCase());

        $this->adapter  = new DbAdaptor($databaseConfiguration);
        $this->metadata = new Metadata($this->adapter);
        $this->adapter->query('set global innodb_stats_on_metadata=0;');
    }

    private function renderToFile(bool $overwrite, string $path, string $template, array $data)
    {
        $output = $this->twig->render($template, $data);
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        if (!file_exists($path) || $overwrite) {
            file_put_contents($path, $output);
        }
    }

    private function pluraliseClassName($className)
    {
        $transCamel2Snake         = new CaseTransformer(new Format\CamelCase(), new Format\SnakeCase());
        $transSnake2Studly        = new CaseTransformer(new Format\SnakeCase(), new Format\StudlyCaps());
        $words                    = explode("_", $transCamel2Snake->transform($className));
        $words[count($words) - 1] = Inflect::pluralize($words[count($words) - 1]);
        $output                   = $transSnake2Studly->transform(implode("_", $words));
        #\Kint::dump($className, $words, $output);
        return $output;
    }

    private function sanitiseModelNameToClassName($modelName)
    {
        if (substr($modelName, 0, 2) == "ld") {
            return substr($modelName, 2);
        } else {
            return $modelName;
        }
    }

    private function getAutoincrementColumns(DbAdaptor $adapter, $table)
    {
        $sql     = "SHOW columns FROM `{$table}` WHERE extra LIKE '%auto_increment%'";
        $query   = $adapter->query($sql);
        $columns = [];

        foreach ($query->execute() as $aiColumn) {
            $columns[] = $aiColumn['Field'];
        }
        return $columns;
    }
    
    private function makeModelSchemas()
    {
        global $argv;
        /**
         * @var $tables \Zend\Db\Metadata\Object\TableObject[]
         */
        $tables = $this->metadata->getTables();

        echo "Collecting " . count($tables) . " entities data.\n";

        $models = [];
        foreach ($tables as $table) {
            if (in_array($table->getName(), $this->ignoredTables)) {
                continue;
            }
            #echo " ??? " . strtolower($table->getName()) . " != " . strtolower($argv[1]) . "\n";
            if (isset($argv[1]) && strtolower($table->getName()) != strtolower($argv[1])) {
                continue;
            }
            $constraints = [];
            foreach ($table->getConstraints() as $constraint) {
                /** @var ConstraintObject $constraint */
                if ($constraint->getType() == "FOREIGN KEY") {
                    $columnAffected               = $constraint->getColumns()[0];
                    $constraints[$columnAffected] = $constraint;

                    $remoteClassName = $this->sanitiseModelNameToClassName($constraint->getReferencedTableName());
                    #echo " *** Detected that {$remoteClassName} has a remote constraint with {$constraint->getTableName()}\n";
                    $models[$remoteClassName]['remote_constraints'][] = $constraint;
                }
            }

            /**
             * @var int                                   $i
             * @var \Zend\Db\Metadata\Object\ColumnObject $column
             */
            foreach ($table->getColumns() as $i => $column) {
                $typeFragments = explode(" ", $column->getDataType());

                /**
                 * Get field properties.
                 */
                $models[$table->getName()]['columns'][$column->getName()] = [
                    'field'            => $this->transCamel2Studly->transform($column->getName()),
                    'type'             => reset($typeFragments),
                    'permitted_values' => $column->getErrata('permitted_values'),
                ];

                /**
                 * Calculate Max Length for field.
                 */
                if (in_array($column->getDataType(), ['int','bigint','tinyint'])) {
                    $maxLength = $column->getNumericPrecision();
                } else {
                    $maxLength = $column->getCharacterMaximumLength();
                }
                switch ($column->getDataType()) {
                    case 'bigint':
                        $maxFieldLength = 9223372036854775807;
                        break;
                    case 'int':
                        $maxFieldLength = 2147483647;
                        break;
                    case 'mediumint':
                        $maxFieldLength = 8388607;
                        break;
                    case 'smallint':
                        $maxFieldLength = 32767;
                        break;
                    case 'tinyint':
                        $maxFieldLength = 127;
                        break;
                    default:
                        $maxFieldLength = null;
                }

                /**
                 * Max field lengths.
                 */
                $models[$table->getName()]['columns'][$column->getName()]['max_length'] = $maxLength;
                if ($maxFieldLength != null) {
                    $models[$table->getName()]['columns'][$column->getName()]['max_field_length'] = $maxFieldLength;
                }

                /**
                 * If there is a default set in the schema, use it.
                 */
                if ($column->getColumnDefault()) {
                    $models[$table->getName()]['columns'][$column->getName()]['default_value'] = $column->getColumnDefault();
                }

                $models[$table->getName()]['columns'][$column->getName()]['max_decimal_places'] = $column->getNumericScale();

                /**
                 * Get relationship constraints.
                 */
                if (isset($constraints[$column->getName()])) {
                    $models[$table->getName()]['columns'][$column->getName()]['constraints'] = $this->makeConstraintArray($constraints[$column->getName()]);
                }
                $models[$table->getName()]['table'] = $table;

                /**
                 * Get Primary Keys.
                 */
                $primaryKeys             = [];
                $primaryParameters       = [];
                $autoincrementParameters = [];
                foreach ($table->getConstraints() as $constraint) {
                    if ($constraint->getType() == 'PRIMARY KEY') {
                        $primaryKeys = $constraint->getColumns();
                    }
                }

                foreach ($this->getAutoincrementColumns($this->adapter, $table->getName()) as $column) {
                    $autoincrementParameters[] = $this->transCamel2Studly->transform($column);
                }

                $models[$table->getName()]['primary_keys']             = $primaryKeys;
                $models[$table->getName()]['primary_parameters']       = $primaryParameters;
                $models[$table->getName()]['autoincrement_parameters'] = $autoincrementParameters;
            }
        }

        foreach ($models as $modelName => $modelData) {
            if (isset($argv[1]) && strtolower($modelName) != strtolower($argv[1])) {
                continue;
            }

            echo " > {$modelName}\n";
            $models[$modelName]['className'] = $this->sanitiseModelNameToClassName($modelName);

            // Decide on column types.
            $columns = [];
            foreach ($modelData['columns'] as $key => $value) {
                switch ($value['type']) {
                    case 'float':
                    case 'decimal':
                        $value['phptype'] = 'float';
                        break;
                    case 'int':
                    case 'bigint':
                    case 'tinyint':
                        $value['phptype'] = 'int';
                        break;
                    case 'varchar':
                    case 'smallblob':
                    case 'blob':
                    case 'longblob':
                    case 'smalltext':
                    case 'text':
                    case 'longtext':
                        $value['phptype'] = 'string';
                        break;
                    case 'enum':
                        $value['phptype'] = 'string';
                        break;
                    case 'datetime':
                        $value['phptype'] = 'string';
                        break;
                    default:
                        echo " > Type not translated: {$value['type']}\n";
                }

                $columns[$key] = $value;
            }
            $models[$modelName]['columns'] = $columns;

            $relatedObjects = [];
            foreach ($columns as $column) {
                if (isset($column['constraints'])) {
                    $relatedObjects[$column['constraints']['remote_model_class']] = $column['constraints'];
                }
            }
            $models[$modelName]['related_objects'] = $relatedObjects;
        }
        return $models;
    }

    private function makeCoreFiles($models)
    {
        $tables = $this->metadata->getTables();

        echo "Generating " . count($tables) . " models.\n";

        $renderData = [];

        foreach ($models as $modelName => $modelData) {
            $className              = $modelData['className'];
            $renderData[$modelName] = [
                'namespace'                => $this->namespace,
                'app_name'                 => APP_NAME,
                'app_container'            => APP_CORE_NAME,
                'class_name'               => $className,
                'variable_name'            => $this->transStudly2Camel->transform($className),
                'name'                     => $modelName,
                'object_name_plural'       => $this->pluraliseClassName($className),
                'object_name_singular'     => $className,
                'controller_route'         => $this->transCamel2Snake->transform(Inflect::pluralize($className)),
                'namespace_model'          => "{$this->namespace}\\Models\\{$className}Model",
                'columns'                  => $modelData['columns'],
                'related_objects'          => $modelData['related_objects'],
                'remote_constraints'       => isset($modelData['remote_constraints']) ? $this->makeConstraintArray($modelData['remote_constraints']) : false,
                'table'                    => $modelName,
                'primary_keys'             => $modelData['primary_keys'],
                'primary_parameters'       => $modelData['primary_parameters'],
                'autoincrement_parameters' => $modelData['autoincrement_parameters']
            ];

            // "Model" suite
            if (in_array("Models", $this->config['templates'])) {
                $this->renderToFile(true, APP_ROOT . "/src/Models/Base/Base{$className}Model.php", "basemodel.php.twig", $renderData[$modelName]);
                $this->renderToFile(false, APP_ROOT . "/src/Models/{$className}Model.php", "model.php.twig", $renderData[$modelName]);
                $this->renderToFile(true, APP_ROOT . "/tests/Models/Generated/{$className}Test.php", "tests.models.php.twig", $renderData[$modelName]);
                $this->renderToFile(true, APP_ROOT . "/src/TableGateways/Base/Base{$className}TableGateway.php", "basetable.php.twig", $renderData[$modelName]);
                $this->renderToFile(false, APP_ROOT . "/src/TableGateways/{$className}TableGateway.php", "table.php.twig", $renderData[$modelName]);
            }

            // "Service" suite
            if (in_array("Services", $this->config['templates'])) {
                $this->renderToFile(true, APP_ROOT . "/src/Services/Base/Base{$className}Service.php", "baseservice.php.twig", $renderData[$modelName]);
                $this->renderToFile(false, APP_ROOT . "/src/Services/{$className}Service.php", "service.php.twig", $renderData[$modelName]);
                $this->renderToFile(true, APP_ROOT . "/tests/Services/Generated/{$className}Test.php", "tests.service.php.twig", $renderData[$modelName]);
            }

            // "Controller" suite
            if (in_array("Controllers", $this->config['templates'])) {
                $this->renderToFile(true, APP_ROOT . "/src/Controllers/Base/Base{$className}Controller.php", "basecontroller.php.twig", $renderData[$modelName]);
                $this->renderToFile(false, APP_ROOT . "/src/Controllers/{$className}Controller.php", "controller.php.twig", $renderData[$modelName]);
            }

            // "Endpoint" test suite
            if (in_array("Endpoints", $this->config['templates'])) {
                $this->renderToFile(true, APP_ROOT . "/tests/Api/Generated/{$className}EndpointTest.php", "tests.endpoints.php.twig", $renderData[$modelName]);
            }

            // "Routes" suit
            if (in_array("Routes", $this->config['templates'])) {
                $this->renderToFile(true, APP_ROOT . "/src/Routes/{$className}Route.php", "route.php.twig", $renderData[$modelName]);
            }

            // "JS" suit
            if (in_array("JsLib", $this->config['templates'])) {
                echo "Generating JS Lib...";
                $this->renderToFile(true, APP_ROOT . "/public/jslib/api.js", "jslib.js.twig", [
                    'models'         => $renderData,
                    'date_generated' => date("Y-m-d H:i:s")
                ]);
                echo "\n > Wrote to " . APP_ROOT . "/public/jslib/api.js";
                echo " [DONE]\n";
                copy(APP_ROOT . "/public/jslib/api.js", APP_ROOT . "/other/api_js_testrig/api.js");
                echo " > Copied to " . APP_ROOT . "/public/jslib/api.js";
                echo " [DONE]\n\n";
            }
        }

        echo "Generating App Container:";
        $this->renderToFile(true, APP_ROOT . "/src/AppContainer.php", "appcontainer.php.twig", ['models' => $renderData, 'config' => $this->config]);
        echo " [DONE]\n";

        // "Routes" suit
        if (in_array("Routes", $this->config['templates'])) {
            echo "Generating Router:";
            $this->renderToFile(true, APP_ROOT . "/src/Routes.php", "routes.php.twig", [
                'models'        => $renderData,
                'app_container' => APP_CORE_NAME,
            ]);
            echo " [DONE]\n";
        }
    }

    private function makeSDKFiles($models, $outputPath = APP_ROOT)
    {
        $packs            = [];
        $routeCount       = 0;
        $sharedRenderData = [
            'app_name'      => APP_NAME,
            'app_container' => APP_CORE_NAME,
        ];

        foreach ($this->getRoutes() as $route) {
            if ($route['name']) {
                if (isset($route['class'])) {
                    $packs[$route['class']][] = $route;
                    $routeCount++;
                } else {
                    echo " > Skipping {$route['name']} because there is no defined Class attached to it...\n";
                }
            }
        }

        echo "Generating SDK for {$routeCount} routes...\n";
        // "SDK" suite
        if (in_array("SDK", $this->config['templates'])) {
            foreach ($packs as $packName => $routes) {
                echo " > Pack: {$packName}...\n";
                $routeRenderData = [
                  'routes' => $routes,
                ];

                $routeRenderData = array_merge($sharedRenderData, $routeRenderData);
                \Kint::dump($routeRenderData);
                $this->renderToFile(true, $outputPath . "/src/AccessLayer/{$packName}AccessLayer.php", "sdk/accesslayer.php.twig", $routeRenderData);
                $this->renderToFile(true, $outputPath . "/src/AccessLayer/Base/Base{$packName}AccessLayer.php", "sdk/baseaccesslayer.php.twig", $routeRenderData);
                $this->renderToFile(true, $outputPath . "/tests/AccessLayer/{$packName}Test.php", "sdk/tests.client.php.twig", $routeRenderData);
            }

            $renderData = array_merge(
                $sharedRenderData,
                [
                    'packs'         => $packs,
                    'config'        => $this->config
                ]
            );

            echo "Generating Abstract Access Layer:";
            $this->renderToFile(true, $outputPath . "/src/Abstracts/AbstractAccessLayer.php", "sdk/abstractaccesslayer.php.twig", $renderData);
            echo " [DONE]\n";

            echo "Generating Client Container:";
            $this->renderToFile(true, $outputPath . "/src/Client.php", "sdk/client.php.twig", $renderData);
            echo " [DONE]\n";

            echo "Generating Composer.json:";
            $this->renderToFile(true, $outputPath . "/composer.json", "sdk/composer.json.twig", $renderData);
            echo " [DONE]\n";
        }
    }

    private function cleanCode()
    {
        if (is_array($this->config['formatting']) && in_array("clean", $this->config['formatting'])) {
            $this->cleanCodePHPCSFixer();
        }
        if (is_array($this->config['formatting']) && in_array("clean", $this->config['formatting'])) {
            $this->cleanCodePSR2();
        }
        $this->cleanCodeComposerAutoloader();
    }

    private function cleanCodePHPCSFixer()
    {
        require(__DIR__ . "/../generator/phpcsfixerfier");
    }

    private function cleanCodePSR2()
    {
        require(__DIR__ . "/../generator/psr2ifier");
    }
    
    private function cleanCodeComposerAutoloader()
    {
        require(__DIR__ . "/../generator/composer-optimise");
    }

    private function getRoutes()
    {
        $response = $this->makeRequest("GET", "/v1");
        $body     = (string) $response->getBody();
        $body     = json_decode($body, true);
        return $body['Routes'];
    }

    /**
     * @param string $method
     * @param string $path
     * @param array  $post
     * @param bool   $isJsonRequest
     *
     * @return Response
     */
    private function makeRequest(string $method, string $path, $post = null, $isJsonRequest = true)
    {
        /**
         * @var \Slim\App $app
         */
        $app         = App::$instance;
        $calledClass = get_called_class();

        if (defined("$calledClass")) {
            $modelName = $calledClass::MODEL_NAME;
            require(APP_ROOT . "/src/Routes/{$modelName}Route.php");
        } else {
            require(APP_ROOT . "/src/Routes.php");
        }
        require(APP_ROOT . "/src/RoutesExtra.php");


        $env = Environment::mock(
            [
                'SCRIPT_NAME'    => '/index.php',
                'REQUEST_URI'    => $path,
                'REQUEST_METHOD' => $method,
                'RAND'           => rand(0, 100000000),
            ]
        );
        $uri     = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);

        $cookies      = [];
        $serverParams = $env->all();
        $body         = new RequestBody();
        if (!is_array($post) && $post != null) {
            $body->write($post);
            $body->rewind();
        } elseif (is_array($post) && count($post) > 0) {
            $body->write(json_encode($post));
            $body->rewind();
        }
        $request = new Request($method, $uri, $headers, $cookies, $serverParams, $body);
        if ($isJsonRequest) {
            $request = $request->withHeader("Content-type", "application/json");
        }
        $response = new Response();
        // Invoke app
        $app($request, $response);
        #echo "\nRequesting {$method}: {$path} : ".json_encode($post) . "\n";
        #echo "Response: " . (string) $response->getBody()."\n";

        return $response;
    }

    private function makeConstraintArray($zendConstraint)
    {
        if (is_array($zendConstraint)) {
            $result = [];
            foreach ($zendConstraint as $zendConstraintElement) {
                $result[] = $this->makeConstraintArray($zendConstraintElement);
            }
            return $result;
        } elseif ($zendConstraint instanceof ConstraintObject) {
            return [
                'field'                         => $zendConstraint->getTableName(),
                'local_model_class'             => $this->sanitiseModelNameToClassName($zendConstraint->getTableName()),
                'remote_model_class'            => $this->sanitiseModelNameToClassName($zendConstraint->getReferencedTableName()),
                'local_model_variable'          => $this->transStudly2Camel->transform($this->sanitiseModelNameToClassName($zendConstraint->getTableName())),
                'remote_model_variable'         => $this->transStudly2Camel->transform($this->sanitiseModelNameToClassName($zendConstraint->getReferencedTableName())),
                'local_model_key'               => $zendConstraint->getColumns()[0],
                'remote_model_key'              => $zendConstraint->getReferencedColumns()[0],
                'local_model_key_get_function'  => $this->transSnake2Studly->transform($zendConstraint->getReferencedColumns()[0]),
                'remote_model_key_get_function' => $this->transSnake2Studly->transform($zendConstraint->getReferencedColumns()[0]),
            ];
        } else {
            throw new \Exception("makeConstraintArray has been passed a non-Zend Constraint.");
        }
    }
}
