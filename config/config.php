<?php

namespace config;

class config {

    /** DB credentials */
    public static $dbHost = 'localhost';

    public static $dbName = 'ping';

    public static $dbUser = 'root';

    public static $dbPass = 'root';

    /* Request auth header name */
    public static $requestAuthHeaderName = 'Api-Key';

    /* Hash password salt */
    public static $passwordHashSalt = 'scZcK39UqIQJGiULyqwPDnHSE31QxsexFrCRWhbLkqaT43223';

    private static $slimDisplayErrorDetails = true;

    private static $slimAddContentLengthHeader = false;

    public static function getSlimConfig() {
        return ['displayErrorDetails' => config::$slimDisplayErrorDetails, 'addContentLengthHeader' => config::$slimAddContentLengthHeader];
    }

}