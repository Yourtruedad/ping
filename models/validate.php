<?php 

namespace models;

use models\users;

class validate {

    public function validateCreateUserParams(array $params) {
        if (!empty($params['username']) AND !empty($password) AND !empty($email)) {

        }
    }

    public function validateEmail(string $email) {
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);
        if (false !== $email) {
            return true;
        }
        return false;
    }

}
