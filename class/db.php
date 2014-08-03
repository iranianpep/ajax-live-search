<?php

if(count(get_included_files()) === 1)
    exit("Direct access not permitted.");

file_exists(__DIR__.DIRECTORY_SEPARATOR.'config.php') ? require_once(__DIR__.DIRECTORY_SEPARATOR.'config.php') : die('There is no such a file: config.php');


class DB
{
    private static $db;

    private function __construct(){
        try {
            self::$db = new PDO('mysql:host='.Config::HOST.';dbname='.Config::DATABASE.';charset=utf8',Config::USERNAME,Config::PASS);
            self::$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

            // To prevent PDO sql injection
            // According to http://stackoverflow.com/a/12202218/2045041
            self::$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
        catch (PDOException $e)
        {
            echo $e->getMessage();
        }
    }

    public static function getConnection() {
        if(!isset(self::$db))
        {
            new DB();
        }

        return self::$db;
    }
}