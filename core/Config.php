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
            'database' => 'your_database',
            'username' => 'your_username',
            'pass' => 'your_pass',
            'table' => 'your_table',
            // specify the name of search columns
            'searchColumns' => array('your_table_search_column'),
            // specify order by column. This is optional
            'orderBy' => '',
            // specify order direction e.g. ASC or DESC. This is optional
            'orderDirection' => '',
            // filter the result by entering table column names
            // to get all the columns, remove filterResult or make it an empty array
            'filterResult' => array(),
            // to limit the maximum number of result uncomment this:
            //'maxResult' => 100
        ),
        // ***** Form ***** //
        // This must be the same as form_anti_bot in script.min.js or script.js
        'antiBot' => "Ehsan's guard",
        // Assigning more than 3 seconds is not recommended
        'searchStartTimeOffset' => 2,
        // ***** Search Input ***** /
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
