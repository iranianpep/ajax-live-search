<?php

namespace AjaxLiveSearch\core;

if (count(get_included_files()) === 1) {
    exit('Direct access not permitted.');
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
     * @param $dbInfo
     */
    private function __construct($dbInfo)
    {
        try {
            // For MySQL version 5.5.3 or greater you can use 'utf8mb4' encoding instead of 'utf8'
            self::$db = new \PDO(
                'mysql:host=' . $dbInfo['host'] . ';dbname=' . $dbInfo['database'] . ';charset=utf8',
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
     * @param  $dbInfo
     * @return \PDO
     */
    public static function getConnection($dbInfo)
    {
        if (!isset(self::$db)) {
            new self($dbInfo);
        }

        return self::$db;
    }
}
