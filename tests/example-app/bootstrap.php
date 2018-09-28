<?php
if (!defined("APP_ROOT")) {
    define("APP_START", microtime(true));
    define("APP_ROOT", __DIR__);
}

ini_set('memory_limit', '512M');
define("APP_NAME", "ReadThat");
define("APP_CORE_NAME", "ReadThat\\ReadThat");

require_once("vendor/autoload.php");
