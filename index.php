<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

require 'vendor/autoload.php';
//require 'config/config.php';

//require 'controllers/main.php';

use controllers\main;
$controller = new main();
$controller->route();


// TESTY

/*print_r($controller->testDbConnection());

use models\users;
$users = new users();
var_dump($users->test());

use config\config;
$config = new config();
var_dump($config::$dbHost);