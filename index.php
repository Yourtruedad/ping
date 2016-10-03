<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

require 'vendor/autoload.php';

use controllers\main;
$controller = new main();
$controller->route();
