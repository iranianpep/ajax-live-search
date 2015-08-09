<?php

namespace AjaxLiveSearch\core;

if (count(get_included_files()) === 1) {
    exit("Direct access not permitted.");
}

/**
 * Class Config
 */
class Config
{
    /**
     * @var array
     */
    private static $configs = array(
        // ***** Database ***** //
        'db' => array(
            'host' => 'localhost',
            'database' => 'live_search',
            'username' => 'root',
            'pass' => 'root',
            'table' => 'live_search_table',
            'searchColumn' => 'name',
//            'filterResult' => array(
//                'id',
//                'name'
//            )
        ),
        'antiBot' => "Ehsan's guard",
        'searchStartTimeOffset' => 3,
        'maxInputLength' => 20
    );

    /**
     * @param $key
     *
     * @return mixed
     * @throws \Exception
     */
    public static function getConfig($key)
    {
        if (!array_key_exists($key, static::$configs)) {
            throw new \Exception("Key: {$key} does not exist in the configs");
        }

        return static::$configs[$key];
    }
}
