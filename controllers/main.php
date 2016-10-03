<?php

namespace controllers;

use PDO;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Interop\Container\ContainerInterface as ContainerInterface;

use config\config as config;
use models\users as users;

class main {

    public $app;

    public $container;

    public function __construct() {
        $this->app = new \Slim\App(["settings" => config::getSlimConfig()]);
        $this->container = $this->app->getContainer();
        $this->createDbConnection();

        // TODO: Ladowanie klas do kontenera musi byc automatyczne - brac dane z bazy
        $this->container['users'] = function ($c) {
            return new users($c);
        };
    }

    /**
     *
     * Create DB connection.
     *
     */
    public function createDbConnection() {
        $this->container['db'] = function ($c) {
            $db = ['host' => config::$dbHost, 'dbname' => config::$dbName, 'user' => config::$dbUser, 'pass' => config::$dbPass];
            $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'], $db['user'], $db['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        };
    }

    // Do wywalenia
    public function testDbConnection() {
        $db = $this->container['db'];
        $query = $db->prepare('SELECT * FROM accounts');
        $query->execute();
        return $query->fetchAll();
    }

    public function findRoute($route, $method) {
        return $route . ' ' . $method . '<br>';
    }

    // Create Monolog container
    /*$container['logger'] = function($c) {
        $logger = new \Monolog\Logger('my_logger');
        $file_handler = new \Monolog\Handler\StreamHandler("logs/app.log");
        $logger->pushHandler($file_handler);
        return $logger;
    };*/
    // Save log
    //$container->logger->addInfo("Something interesting happened");

    // Create DB connection container
    /*$container['db'] = function ($c) {
        $db = $c['settings']['db'];
        $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'], $db['user'], $db['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    };*/

    //use models\users;
    //$users = new users($container->db);
    //var_dump($users->getAccounts());
    //$users = new users(1);


    /*$app->get('/hello/{name}', function (Request $request, Response $response) {
        $name = $request->getAttribute('name');
        $response->getBody()->write("Hello, $name");
        //$container->logger->addInfo("route hello");
        //$users = new users($container->db);
        return $response;
    });
    $app->get('/hello2/{name}', function (Request $request, Response $response) {
        $name = $request->getAttribute('name');
        $response->getBody()->write("Hello2, $name");

        return $response;
    });
    $app->run();*/

    public function route() {
        /*$this->app->get('/hello/{name}', function (Request $request, Response $response) {
            var_dump($request->getMethod());
            $name = $request->getAttribute('name');
            $response->getBody()->write("Hello, $name");
            //$container->logger->addInfo("route hello");
            //$users = new users($container->db);
            return $response;
        });*/

        // Zakladanie nowego konta 
        $this->app->post('/user', 'users:createUser');

        // Pobieranie danych wszystkich uzytkownikow
        // $this->app->get('/users', 'users:getUsers');

        // Pobieranie danych konta 
        $this->app->get('/user', 'users:getUser');

        // Logowanie do konta 
        $this->app->post('/user/authenticate', 'users:authenticateUser');
        return $this->app->run();
    }

}