<?php

    namespace AjaxLiveSearch\core;

if (count(get_included_files()) === 1) {
    exit("Direct access not permitted.");
}

/**
 * Class DB
 */
class DB
{
    /**
     * @var \PDO
     */
    private static $db;

    /**
     * @throws \Exception
     */
    private function __construct()
    {
        $dbInfo = Config::getConfig('db');

        try {
            self::$db = new \PDO(
                'mysql:host='.$dbInfo['host'].';dbname='.$dbInfo['database'].';charset=utf8',
                $dbInfo['username'],
                $dbInfo['pass']
            );

            self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // To prevent PDO sql injection
            // According to http://stackoverflow.com/a/12202218/2045041
            self::$db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @return \PDO
     */
    public static function getConnection()
    {
        if (!isset(self::$db)) {
            new DB();
        }

        return self::$db;
    }
}
