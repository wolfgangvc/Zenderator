<?php
namespace Zenderator;

use Monolog\Handler\RedisHandler;
use Monolog\Handler\SlackHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Slim;
use Faker\Provider;
use Faker\Factory as FakerFactory;
use Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware;

class App
{

    static $instance;

    /** @var \Slim\App  */
    protected $app;
    /** @var \Interop\Container\ContainerInterface */
    protected $container;
    /** @var Logger*/
    protected $monolog;

    /**
     * @return App
     */
    public static function Instance($doNotUseStaticInstance = false)
    {
        if (!self::$instance || $doNotUseStaticInstance === true) {
            $calledClass = get_called_class();
            self::$instance = new $calledClass();
        }
        return self::$instance;
    }

    /**
     * @return \Interop\Container\ContainerInterface
     */
    public static function Container()
    {
        return self::Instance()->getContainer();
    }

    /**
     * @return \Interop\Container\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function getApp()
    {
        return $this->app;
    }

    public function __construct()
    {
        // Check defined config
        if(!defined("APP_NAME")){
            throw new \Exception("APP_NAME must be defined in /bootstrap.php");
        }
        
        // Create Slim app
        $this->app = new \Slim\App(
            [
                'settings' => [
                    'debug' => true,
                    'displayErrorDetails' => true,
                ]
            ]
        );

        // Add whoops to slim because its helps debuggin' and is pretty.
        $this->app->add(new WhoopsMiddleware());

        // Fetch DI Container
        $this->container = $this->app->getContainer();

        // Register Twig View helper
        $this->container['view'] = function ($c) {
            $view = new \Slim\Views\Twig(
                '../views/',
                [
                'cache' => false,
                'debug' => true
                ]
            );

            // Instantiate and add Slim specific extension
            $view->addExtension(
                new Slim\Views\TwigExtension(
                    $c['router'],
                    $c['request']->getUri()
                )
            );

            // Added Twig_Extension_Debug to enable twig dump() etc.
            $view->addExtension(
                new \Twig_Extension_Debug()
            );

            $view->addExtension(new \Twig_Extensions_Extension_Text());

            return $view;
        };

        $this->container['DatabaseInstance'] = function (Slim\Container $c) {
            return Db::getInstance();
        };

        $this->container['Faker'] = function (Slim\Container $c) {
            $faker = FakerFactory::create();
            $faker->addProvider(new Provider\Base($faker));
            $faker->addProvider(new Provider\DateTime($faker));
            $faker->addProvider(new Provider\Lorem($faker));
            $faker->addProvider(new Provider\Internet($faker));
            $faker->addProvider(new Provider\Payment($faker));
            $faker->addProvider(new Provider\en_US\Person($faker));
            $faker->addProvider(new Provider\en_US\Address($faker));
            $faker->addProvider(new Provider\en_US\PhoneNumber($faker));
            $faker->addProvider(new Provider\en_US\Company($faker));
            return $faker;
        };

        $this->container['Environment'] = function (Slim\Container $c) {
            $environment = array_merge($_ENV, $_SERVER);
            ksort($environment);
            return $environment;
        };

        require(APP_ROOT . "/src/AppContainer.php");

        // Get environment variables.
        $environment = $this->getContainer()->get('Environment');
        
        // Set up Redis.
        $redisConfig = parse_url($environment['REDIS_PORT']);
        if(isset($environment['REDIS_OVERRIDE_HOST'])){
            $redisConfig['host'] = $environment['REDIS_OVERRIDE_HOST'];
        }
        if(isset($environment['REDIS_OVERRIDE_PORT'])){
            $redisConfig['port'] = $environment['REDIS_OVERRIDE_PORT'];
        }
        $this->redis = new \Predis\Client($redisConfig);
        
        // Set up Monolog
        $this->monolog = new Logger(APP_NAME);
        $this->monolog->pushHandler(new StreamHandler("logs/" . APP_NAME . "." . date("Y-m-d") . ".log", Logger::WARNING));
        $this->monolog->pushHandler(new RedisHandler($this->redis, "Logs", Logger::DEBUG));
        if(isset($environment['SLACK_TOKEN']) && isset($environment['SLACK_CHANNEL'])) {
            $this->monolog->pushHandler(
                new SlackHandler(
                    $environment['SLACK_TOKEN'],
                    $environment['SLACK_CHANNEL'],
                    APP_NAME,
                    true,
                    null,
                    Logger::CRITICAL
                )
            );
        }
    }

    static public function Log(int $level = Logger::DEBUG, $message)
    {
        return self::Instance()->monolog->log($level, $message);
    }

    public function loadAllRoutes()
    {
        require(APP_ROOT . "/src/Routes.php");
        return $this;
    }
}
