<?php

namespace Zenderator;

use Zend\Db\Adapter\Adapter;

class Db
{
    static private $instance;

    /**
     * @return Adapter
     */
    public static function getInstance()
    {
        if (!self::$instance instanceof Adapter) {
            $dbConfig = include APP_ROOT . "/config/mysql.php";
            self::$instance = new Adapter($dbConfig);
        }
        return self::$instance;
    }
}
