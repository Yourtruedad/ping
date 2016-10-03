<?php 

namespace models;

class common {

    public function checkIfArrayKeysExist(array $array, array $keys) {
        foreach ($keys as $key) {
            if (false === array_key_exists($key, $array)) {
                return false;
            }
        }
        return true;
    }

    public function formatJsonErrorResponse($responseCode, $response, $statusCode) {
        return ['statusCode' => $statusCode, $responseCode => $response];
    }

}