<?php 

namespace models;

use PDO;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \controllers\main;
use models\common;
use models\validate;

use config\config;

class users extends main {

    private $db;

    private $common;

    private $validate;

    private $maxUsernameLength = 64;

    public function __construct($container) {
        $this->db = $container['db'];
        $this->common = new common();
        $this->validate = new validate();
    }

    /**
     * @api {post} /user/ New user
     * @apiDescription Setting up a new user account.
     * @apiName POST /user
     * @apiGroup Users
     * @apiVersion 0.1.0
     *
     * @apiParam {String} email Mandatory email address.
     * @apiParam {String} password Mandatory password.
     *
     * @apiSuccess {String} - User Api Key.
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "1049b9eeabb321798542b4e1d2ab8866"
     *     }
     */
    public function createUser(Request $request, Response $response, array $args) {
        if (true === $request->isPost()) {
            $requestBody = $request->getParsedBody();
            $requiredParams = ['password', 'email'];
            if (true === $this->common->checkIfArrayKeysExist($requestBody, $requiredParams)) {
                $password = $this->hashPassword(filter_var($requestBody['password'], FILTER_SANITIZE_STRING));
                $email = filter_var($requestBody['email'], FILTER_SANITIZE_EMAIL);
                $apiKey = $this->createApiKey();
                if (!empty($password) AND !empty($email)) {
                    if (!empty($apiKey)) {
                        if(true === $this->validate->validateEmail($email)) {
                            if (false === $this->checkIfEmailInUse($email)) {
                                $query = $this->db->prepare('INSERT INTO users (password, email, api_key) VALUES (:password, :email, :api_key)');
                                //$query->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                                $query->bindParam(':password', $password, PDO::PARAM_STR);
                                $query->bindParam(':email', $email, PDO::PARAM_STR);
                                $query->bindParam(':api_key', $apiKey, PDO::PARAM_STR);
                                $result = $query->execute();
                                if (true === $result) {
                                    return $response->withJson($apiKey);
                                } else {
                                    return $response->withJson($this->common->formatJsonErrorResponse('CreateUserError', 'Internal error', 500), 500);
                                }
                            } else {
                                return $response->withJson($this->common->formatJsonErrorResponse('EmailInUse', 'Email already in use', 403), 403);
                            }
                        } else {
                            return $response->withJson($this->common->formatJsonErrorResponse('InvalidEmail', 'Invalid email format', 403), 403);
                        }
                    } else {
                        return $response->withJson($this->common->formatJsonErrorResponse('ApiKeyError', 'API Key Error', 500), 500);
                    }
                } else {
                    return $response->withJson($this->common->formatJsonErrorResponse('ParamValuesMissing', 'Required parameter values missing', 400), 400);
                }
            } else {
                return $response->withJson($this->common->formatJsonErrorResponse('ParamsMissing', 'Required parameters missing', 400), 400);
            }
        }
    }

    private function hashPassword($password) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['salt' => config::$passwordHashSalt]);
        if (false !== $hash) {
            return $hash;
        }
        return '';
    }

    private function checkIfUsernameInUse(string $username) {
        $query = $this->db->prepare('SELECT username FROM users WHERE username = :username');
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_COLUMN);

        if (!empty($result)) {
            return true;
        }
        return false;
    }

    private function checkIfEmailInUse(string $email) {
        $query = $this->db->prepare('SELECT email FROM users WHERE email = :email');
        $query->bindParam(':email', $email, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_COLUMN);

        if (!empty($result)) {
            return true;
        }
        return false;
    }

    private function createApiKey() {
        $query = $this->db->prepare('SELECT api_key FROM users');
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_COLUMN);

        $apiKey = $this->generateApiKey();
        $apiKeyGenerationStatus = false;
        $loopCounter = 0;
        while ($apiKeyGenerationStatus == false AND $loopCounter < 100) {
            if (!in_array($apiKey, $results)) {
                $apiKeyGenerationStatus = true;
                break;
            } else {
                $apiKey = $this->generateApiKey();
            }
            $loopCounter++;
        }
        return $apiKey;
    }

    private function generateApiKey(int $length = 32) {
        $apiKey = '';
        for ($loop = 0; $loop < $length; $loop++) {
            $apiKey .= chr(rand(0,255));
        }
        return md5($apiKey);
    }

    // EVIL
    /*public function getUsers(Request $request, Response $response, array $args) {
        if (true === $request->isGet()) {
            $query = $this->db->prepare('SELECT id, username, password, email, api_key FROM users');
            $query->execute();
            $results = $query->fetchAll();
            return $response->withJson($results);
        }
    }*/

    /**
     * @api {get} /user/ User details
     * @apiDescription Getting user details.
     * @apiName GET /user
     * @apiGroup Users
     * @apiVersion 0.1.0
     *
     * @apiParam {String} apikey User Api Key.
     *
     * @apiSuccess {Array} - List of user details.
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "id": "10",
     *       "email": "test@example.com",
     *       "created_on": "2016-10-02 13:42:59"
     *     }
     */
    public function getUser(Request $request, Response $response, array $args) {
        if (true === $request->isGet()) {
            $apiKey = implode('', $request->getHeader(config::$requestAuthHeaderName));
            $apiKey = filter_var($apiKey, FILTER_SANITIZE_STRING);
            $user = $this->getUserByApiKey($apiKey);
            if (!empty($user)) {
                return $response->withJson($user);
            } else {
                return $response->withJson($this->common->formatJsonErrorResponse('NoDetailsFound', 'No user details found', 500), 500);
            }
        }
    }

    private function getUserByApiKey(string $apiKey) {
        if (!empty($apiKey)) {
            $query = $this->db->prepare('SELECT id, email, created_on FROM users WHERE api_key = :api_key');
            $query->bindParam(':api_key', $apiKey, PDO::PARAM_STR);
            $query->execute();
            $result = $query->fetch();
            if (!empty($result)) {
                return $result;
            }
            return false;
        }
        return false;
    }

    /**
     * @api {post} /user/authenticate Authenticate
     * @apiDescription Getting Api Key with account credentials.
     * @apiName POST /user/authenticate
     * @apiGroup Users
     * @apiVersion 0.1.0
     *
     * @apiParam {String} email Mandatory email address.
     * @apiParam {String} password Mandatory password.
     *
     * @apiSuccess {String} - User Api Key.
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "1049b9eeabb321798542b4e1d2ab8866"
     *     }
     * @apiError InvalidLogin Invalid credentials.
     * @apiError ParamValuesMissing Required parameter values missing.
     * @apiError ParamsMissing Required parameters missing.
     * @apiErrorExample {json} Error-Response:
     *     HTTP/1.1 400 Not Found
     *     {
     *       "statusCode": "400",
     *       "ParamsMissing": "Required parameters missing"
     *     }
     */
    public function authenticateUser(Request $request, Response $response, array $args) {
        if (true === $request->isPost()) {
            $requestBody = $request->getParsedBody();
            $requiredParams = ['password', 'email'];
            if (true === $this->common->checkIfArrayKeysExist($requestBody, $requiredParams)) {
                $password = $this->hashPassword(filter_var($requestBody['password'], FILTER_SANITIZE_STRING));
                $email = filter_var($requestBody['email'], FILTER_SANITIZE_EMAIL);
                if (!empty($password) AND !empty($email)) {
                    $query = $this->db->prepare('SELECT api_key FROM users WHERE email = :email AND password = :password LIMIT 1');
                    //$query->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                    $query->bindParam(':password', $password, PDO::PARAM_STR);
                    $query->bindParam(':email', $email, PDO::PARAM_STR);
                    $query->execute();
                    $result = $query->fetch();
                    if (!empty($result)) {
                        return $response->withJson($result);
                    } else {
                        return $response->withJson($this->common->formatJsonErrorResponse('InvalidLogin', 'Invalid credentials', 401), 401);
                    }
                } else {
                    return $response->withJson($this->common->formatJsonErrorResponse('ParamValuesMissing', 'Required parameter values missing', 400), 400);
                }
            } else {
                return $response->withJson($this->common->formatJsonErrorResponse('ParamsMissing', 'Required parameters missing', 400), 400);
            }
        }
    }

    /* TESTY */

    public function test() {

        //return 'test class';
        var_dump($this->db);
        return $this->db;

    }

    public function test2() {

        $query = $this->db->prepare('INSERT INTO test (test) VALUES (1)');
        $query->execute();
    }

    public function test3() {
        $query = $this->db->prepare('SELECT * FROM accounts');
        $query->execute();
        return $query->fetchAll();
    }

}
