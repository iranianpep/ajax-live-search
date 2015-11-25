<?php
namespace AjaxLiveSearch\core;

file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'DB.php') ? require_once __DIR__ . DIRECTORY_SEPARATOR . 'DB.php' : die('There is no such a file: DB.php');
file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'Config.php') ? require_once __DIR__ . DIRECTORY_SEPARATOR . 'Config.php' : die('There is no such a file: Config.php');

if (count(get_included_files()) === 1) {
    exit('Direct access not permitted.');
}

if (session_id() == '') {
    session_start();
}

/**
 * Class Handler
 */
class Handler
{
    /**
     * returns a 32 bits token and resets the old token if exists
     *
     * @return string
     */
    public static function getToken()
    {
        // create a form token to protect against CSRF
        $token = bin2hex(openssl_random_pseudo_bytes(32));
        return $_SESSION['ls_session']['token'] = $token;
    }

    /**
     * receives a posted variable and checks it against the same one in the session
     *
     * @param  $session_parameter
     * @param  $session_value
     * @return bool
     */
    public static function verifySessionValue($session_parameter, $session_value)
    {
        $white_list = array('token', 'anti_bot');

        if (in_array($session_parameter, $white_list) &&
            $_SESSION['ls_session'][$session_parameter] === $session_value
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * checks required fields, max length for search input and numbers for pagination
     *
     * @param  $input_array
     * @return array
     */
    public static function validateInput($input_array)
    {
        $error = array();

        foreach ($input_array as $k => $v) {
            if (!isset($v) || (trim($v) == '' && $v != '0') || $v == null) {
                array_push($error, $k);
            } elseif ($k === 'ls_current_page' || $k === 'ls_items_per_page') {
                if ((int) $v < 0) {
                    array_push($error, $k);
                }
            } elseif ($k === 'ls_query' && strlen($v) > Config::getConfig('maxInputLength')) {
                array_push($error, $k);
            }
        }

        return $error;
    }

    /**
     * forms the response object including
     * status (success or failed)
     * message
     * result (html result)
     *
     * @param $status
     * @param $message
     * @param string     $result
     */
    public static function formResponse($status, $message, $result = '')
    {
        $css_class = ($status === 'failed') ? 'error' : 'success';

        $message = "<tr><td class='{$css_class}'>{$message}</td></tr>";

        echo json_encode(array('status' => $status, 'message' => $message, 'result' => $result));
    }

    /**
     * @param     $query_id: This is html id
     * @param     $query
     * @param int $current_page
     * @param int $items_per_page
     *
     * @return array
     * @throws \Exception
     */
    public static function getResult($query_id, $query, $current_page = 1, $items_per_page = 0)
    {
        // get data sources list
        $dataSources = Config::getConfig('dataSources');

        if (!isset($dataSources[$query_id])) {
            throw new \Exception("There is no data info for {$query_id}");
        }

        // get info for the selected data source
        $dbInfo = $dataSources[$query_id];

        switch ($dbInfo['type']) {
            case 'mysql':
                return self::getDataFromMySQL($dbInfo, $query, $current_page, $items_per_page);
                break;
            case 'mongo':
                return self::getDataFromMongo($dbInfo, $query, $current_page, $items_per_page);
                break;
            default:
                return self::getDataFromMySQL($dbInfo, $query, $current_page, $items_per_page);
        }
    }

    /**
     * @param  $dbInfo
     * @param  $query
     * @param  $current_page
     * @param  $items_per_page
     * @throws \Exception
     * @return array
     */
    private static function getDataFromMySQL($dbInfo, $query, $current_page, $items_per_page)
    {
        // get connection
        $db = DB::getConnection($dbInfo);

        $sql = "SELECT COUNT(*) FROM {$dbInfo['table']}";

        // append where clause if search columns is set in the config
        $whereClause = '';
        if (!empty($dbInfo['searchColumns'])) {
            $whereClause .= ' WHERE';
            $counter = 1;

            $binary = $dbInfo['caseSensitive'] == true ? 'BINARY' : '';

            switch ($dbInfo['comparisonOperator']) {
                case '=':
                    $comparisonOperator = '=';
                    break;
                case 'LIKE':
                    $comparisonOperator = 'LIKE';
                    break;
                default:
                    throw new \Exception('Comparison Operator is not valid');
            }

            foreach ($dbInfo['searchColumns'] as $searchColumn) {
                if ($counter == count($dbInfo['searchColumns'])) {
                    // last item
                    $whereClause .= " {$binary} {$searchColumn} {$comparisonOperator} :query{$counter}";
                } else {
                    $whereClause .= " {$binary} {$searchColumn} {$comparisonOperator} :query{$counter} OR";
                }

                ++$counter;
            }
            $sql .= $whereClause;
        }

        // get the number of total result
        $stmt = $db->prepare($sql);

        if (!empty($whereClause)) {
            switch ($dbInfo['searchPattern']) {
                case 'q':
                    $search_query = $query;
                    break;
                case '*q':
                    $search_query = "%{$query}";
                    break;
                case 'q*':
                    $search_query = "{$query}%";
                    break;
                case '*q*':
                    $search_query = "%{$query}%";
                    break;
                default:
                    throw new \Exception('Search Pattern is not valid');
            }

            for ($i = 1; $i <= count($dbInfo['searchColumns']); ++$i) {
                $toBindQuery = ':query' . $i;
                $stmt->bindParam($toBindQuery, $search_query, \PDO::PARAM_STR);
            }
        }

        $stmt->execute();
        $number_of_result = (int) $stmt->fetch(\PDO::FETCH_COLUMN);

        if (isset($dbInfo['maxResult']) && $number_of_result > $dbInfo['maxResult']) {
            $number_of_result = $dbInfo['maxResult'];
        }

        // initialize variables
        $HTML = '';
        $number_of_pages = 1;

        if (!empty($number_of_result) && $number_of_result !== 0) {
            if (!empty($dbInfo['filterResult'])) {
                $fromColumn = implode(',', $dbInfo['filterResult']);
            } else {
                $fromColumn = '*';
            }

            $baseSQL = "SELECT {$fromColumn} FROM {$dbInfo['table']}";

            if (!empty($whereClause)) {
                // set order by
                $orderBy = !empty($dbInfo['orderBy']) ? $dbInfo['orderBy'] : $dbInfo['searchColumns'][0];

                // set order direction
                $allowedOrderDirection = array('ASC', 'DESC');
                if (!empty($dbInfo['orderDirection']) && in_array($dbInfo['orderDirection'], $allowedOrderDirection)) {
                    $orderDirection = $dbInfo['orderDirection'];
                } else {
                    $orderDirection = 'ASC';
                }

                $baseSQL .= "{$whereClause} ORDER BY {$orderBy} {$orderDirection}";
            }

            if ($items_per_page === 0) {
                if (isset($dbInfo['maxResult'])) {
                    $baseSQL .= " LIMIT {$dbInfo['maxResult']}";
                }

                // show all
                $stmt = $db->prepare($baseSQL);

                if (!empty($whereClause)) {
                    for ($i = 1; $i <= count($dbInfo['searchColumns']); ++$i) {
                        $toBindQuery = ':query' . $i;
                        $stmt->bindParam($toBindQuery, $search_query, \PDO::PARAM_STR);
                    }
                }
            } else {
                /*
                 * pagination
                 *
                 * calculate total pages
                 */
                if ($number_of_result < $items_per_page) {
                    $number_of_pages = 1;
                } elseif ($number_of_result > $items_per_page) {
                    if ($number_of_result % $items_per_page === 0) {
                        $number_of_pages = floor($number_of_result / $items_per_page);
                    } else {
                        $number_of_pages = floor($number_of_result / $items_per_page) + 1;
                    }
                } else {
                    $number_of_pages = $number_of_result / $items_per_page;
                }

                if (isset($dbInfo['maxResult'])) {
                    // calculate the limit
                    if ($current_page == 1) {
                        if ($items_per_page > $dbInfo['maxResult']) {
                            $limit = $dbInfo['maxResult'];
                        } else {
                            $limit = $items_per_page;
                        }
                    } elseif ($current_page == $number_of_pages) {
                        // last page
                        $limit = $dbInfo['maxResult'] - (($current_page - 1) * $items_per_page);
                    } else {
                        $limit = $items_per_page;
                    }
                } else {
                    $limit = $items_per_page;
                }

                /*
                 * pagination
                 *
                 * calculate start
                 */
                $start = ($current_page > 0) ? ($current_page - 1) * $items_per_page : 0;

                $stmt = $db->prepare(
                    "{$baseSQL} LIMIT {$start}, {$limit}"
                );

                if (!empty($whereClause)) {
                    for ($i = 1; $i <= count($dbInfo['searchColumns']); ++$i) {
                        $toBindQuery = ':query' . $i;
                        $stmt->bindParam($toBindQuery, $search_query, \PDO::PARAM_STR);
                    }
                }
            }

            // run the query and get the result
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (isset($dbInfo['displayHeader']['active']) && $dbInfo['displayHeader']['active'] == true) {
                $headers = array_keys($results[0]);

                $mapper = !empty($dbInfo['displayHeader']['mapper']) ? $dbInfo['displayHeader']['mapper'] : array();

                // generate header
                $HTML .= '<tr>';
                foreach ($headers as $aHeader) {
                    $aHeader = array_key_exists($aHeader, $mapper) ? $mapper[$aHeader] : $aHeader;
                    $HTML .= "<th>{$aHeader}</th>";
                }
                $HTML .= '</tr>';
            }
            
            // generate HTML
            foreach ($results as $result) {
                $HTML .= '<tr>';
                foreach ($result as $column) {
                    $HTML .= "<td>{$column}</td>";
                }
                $HTML .= '</tr>';
            }
        } else {
            // To prevent XSS prevention convert user input to HTML entities
            $query = htmlentities($query, ENT_NOQUOTES, 'UTF-8');

            // there is no result - return an appropriate message.
            $HTML .= "<tr><td>There is no result for \"{$query}\"</td></tr>";
        }

        // form the return
        return array(
            'html'              => $HTML,
            'number_of_results' => (int) $number_of_result,
            'total_pages'       => $number_of_pages,
        );
    }

    /**
     * @param $dbInfo
     * @param $query
     * @param $current_page
     * @param $items_per_page
     * @return array
     * @throws \Exception
     */
    private static function getDataFromMongo($dbInfo, $query, $current_page, $items_per_page)
    {
        $mongoClient = new \MongoClient($dbInfo['server']);
        $database = $mongoClient->selectDB($dbInfo['database']);
        $collection = $database->selectCollection($dbInfo['collection']);

        $searchField = $dbInfo['searchField'];
        $regex = new \MongoRegex("/^{$query}/i");
        $criteria = array($searchField => $regex);
        $results = $collection->find($criteria, $dbInfo['filterResult']);

        if (!$results instanceof \MongoCursor) {
            throw new \Exception('There is an issue getting data from Mongodb');
        }

        $number_of_result = $results->count();
        $start = ($current_page > 0) ? ($current_page - 1) * $items_per_page : 0;
        $results = $results->limit($items_per_page)->skip($start);

        $HTML = '';

        /*
         * pagination
         *
         * calculate total pages
         */
        if ($number_of_result < $items_per_page) {
            $number_of_pages = 1;
        } elseif ($number_of_result > $items_per_page) {
            if ($number_of_result % $items_per_page === 0) {
                $number_of_pages = floor($number_of_result / $items_per_page);
            } else {
                $number_of_pages = floor($number_of_result / $items_per_page) + 1;
            }
        } else {
            $number_of_pages = $number_of_result / $items_per_page;
        }

        if ($number_of_result > 0) {
            foreach ($results as $result) {
                $HTML .= '<tr>';
                foreach ($result as $column) {
                    if (is_array($column)) {
                        $content = '';
                        foreach ($column as $aColumnKey => $aColumnValue) {
                            $content .= "{$aColumnKey} : {$aColumnValue} ";
                        }

                        $HTML .= "<td>{$content}</td>";
                    } else {
                        $HTML .= "<td>{$column}</td>";
                    }
                }
                $HTML .= '</tr>';
            }
        } else {
            // To prevent XSS prevention convert user input to HTML entities
            $query = htmlentities($query, ENT_NOQUOTES, 'UTF-8');

            // there is no result - return an appropriate message.
            $HTML .= "<tr><td>There is no result for \"{$query}\"</td></tr>";
        }

        // form the return
        return array(
            'html' => $HTML,
            'number_of_results' => (int) $number_of_result,
            'total_pages' => $number_of_pages,
        );
    }

    /**
     * @return string
     */
    public static function getJavascriptAntiBot()
    {
        return $_SESSION['ls_session']['anti_bot'] = Config::getConfig('antiBot');
    }

    /**
     * Calculate the timestamp difference between the time page is loaded
     * and the time searching is started for the first time in seconds
     *
     * @param  $page_loaded_at
     * @return bool
     */
    public static function verifyBotSearched($page_loaded_at)
    {
        // if searching starts less than start time offset it seems it's a Bot
        return (time() - $page_loaded_at < Config::getConfig('searchStartTimeOffset')) ? false : true;
    }
}
