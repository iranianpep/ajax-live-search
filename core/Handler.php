<?php

namespace AjaxLiveSearch\core;

$DS = DIRECTORY_SEPARATOR;
file_exists(__DIR__ . $DS . 'DB.php') ? require_once __DIR__ . $DS . 'DB.php' : die('DB.php not found');
file_exists(__DIR__ . $DS . 'Config.php') ? require_once __DIR__ . $DS . 'Config.php' : die('Config.php not found');

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
    public function getToken()
    {
        // create a form token to protect against CSRF
        return $_SESSION['ls_session']['token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }

    /**
     * receives a posted variable and checks it against the same one in the session
     *
     * @param  $sessionParameter
     * @param  $sessionValue
     * @return bool
     */
    public function verifySessionValue($sessionParameter, $sessionValue)
    {
        $whiteList = ['token', 'anti_bot'];

        if (in_array($sessionParameter, $whiteList) && isset($_SESSION['ls_session']) &&
            $_SESSION['ls_session'][$sessionParameter] === $sessionValue
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * checks required fields, max length for search input and numbers for pagination
     *
     * @param  $inputArray
     * @return array
     */
    public function validateInput($inputArray)
    {
        $error = [];

        $maxInputLength = Config::getConfig('maxInputLength');

        if (!empty($inputArray)) {
            foreach ($inputArray as $k => $v) {
                if ($k === 'ls_query' && strlen($v) > $maxInputLength) {
                    array_push($error, $k);
                } elseif ($k === 'ls_current_page' || $k === 'ls_items_per_page') {
                    if ((int) $v < 0) {
                        array_push($error, $k);
                    }
                }
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
    public function formResponse($status, $message, $result = '')
    {
        $cssClass = ($status === 'failed') ? 'error' : 'success';

        $message = "<tr><td class='{$cssClass}'>{$message}</td></tr>";

        echo json_encode([
            'status' => $status,
            'message' => $message,
            'result' => $result
        ]);
        exit;
    }

    /**
     * @param     $searchFieldId: This is html id
     * @param     $query
     * @param int $currentPage
     * @param int $perPage
     *
     * @return array
     * @throws \Exception
     */
    public function getData($searchFieldId, $query, $currentPage = 1, $perPage = 0)
    {
        // get data sources list
        $dataSources = Config::getConfig('dataSources');

        if (!isset($dataSources[$searchFieldId])) {
            throw new \Exception("There is no data info for {$searchFieldId}");
        }

        // get info for the selected data source
        $dbInfo = $dataSources[$searchFieldId];

        switch ($dbInfo['type']) {
            case 'mysql':
                return $this->getDataFromMySQL($dbInfo, $query, $currentPage, $perPage);
                break;
            case 'mongo':
                return $this->getDataFromMongo($dbInfo, $query, $currentPage, $perPage);
                break;
            default:
                return $this->getDataFromMySQL($dbInfo, $query, $currentPage, $perPage);
        }
    }

    /**
     * @param  $dbInfo
     * @param  $query
     * @param  $currentPage
     * @param  $perPage
     * @throws \Exception
     * @return array
     */
    private function getDataFromMySQL($dbInfo, $query, $currentPage, $perPage)
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
                    $searchQuery = $query;
                    break;
                case '*q':
                    $searchQuery = "%{$query}";
                    break;
                case 'q*':
                    $searchQuery = "{$query}%";
                    break;
                case '*q*':
                    $searchQuery = "%{$query}%";
                    break;
                default:
                    throw new \Exception('Search Pattern is not valid');
            }

            for ($i = 1; $i <= count($dbInfo['searchColumns']); ++$i) {
                $toBindQuery = ':query' . $i;
                $stmt->bindParam($toBindQuery, $searchQuery, \PDO::PARAM_STR);
            }
        }

        $stmt->execute();
        $resultNumber = (int) $stmt->fetch(\PDO::FETCH_COLUMN);

        if (isset($dbInfo['maxResult']) && $resultNumber > $dbInfo['maxResult']) {
            $resultNumber = $dbInfo['maxResult'];
        }

        // initialize variables
        $HTML = '';
        $pagesNumber = 1;

        if (!empty($resultNumber) && $resultNumber !== 0) {
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
                $allowedOrderDirection = ['ASC', 'DESC'];
                if (!empty($dbInfo['orderDirection']) && in_array($dbInfo['orderDirection'], $allowedOrderDirection)) {
                    $orderDirection = $dbInfo['orderDirection'];
                } else {
                    $orderDirection = 'ASC';
                }

                $baseSQL .= "{$whereClause} ORDER BY {$orderBy} {$orderDirection}";
            }

            if ($perPage === 0) {
                if (isset($dbInfo['maxResult'])) {
                    $baseSQL .= " LIMIT {$dbInfo['maxResult']}";
                }

                // show all
                $stmt = $db->prepare($baseSQL);

                if (!empty($whereClause)) {
                    for ($i = 1; $i <= count($dbInfo['searchColumns']); ++$i) {
                        $toBindQuery = ':query' . $i;
                        $stmt->bindParam($toBindQuery, $searchQuery, \PDO::PARAM_STR);
                    }
                }
            } else {
                /*
                 * pagination
                 *
                 * calculate total pages
                 */
                if ($resultNumber < $perPage) {
                    $pagesNumber = 1;
                } elseif ($resultNumber > $perPage) {
                    if ($resultNumber % $perPage === 0) {
                        $pagesNumber = floor($resultNumber / $perPage);
                    } else {
                        $pagesNumber = floor($resultNumber / $perPage) + 1;
                    }
                } else {
                    $pagesNumber = $resultNumber / $perPage;
                }

                if (isset($dbInfo['maxResult'])) {
                    // calculate the limit
                    if ($currentPage == 1) {
                        if ($perPage > $dbInfo['maxResult']) {
                            $limit = $dbInfo['maxResult'];
                        } else {
                            $limit = $perPage;
                        }
                    } elseif ($currentPage == $pagesNumber) {
                        // last page
                        $limit = $dbInfo['maxResult'] - (($currentPage - 1) * $perPage);
                    } else {
                        $limit = $perPage;
                    }
                } else {
                    $limit = $perPage;
                }

                /*
                 * pagination
                 *
                 * calculate start
                 */
                $start = ($currentPage > 0) ? ($currentPage - 1) * $perPage : 0;

                $stmt = $db->prepare(
                    "{$baseSQL} LIMIT {$start}, {$limit}"
                );

                if (!empty($whereClause)) {
                    for ($i = 1; $i <= count($dbInfo['searchColumns']); ++$i) {
                        $toBindQuery = ':query' . $i;
                        $stmt->bindParam($toBindQuery, $searchQuery, \PDO::PARAM_STR);
                    }
                }
            }

            // run the query and get the result
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // if requested, generate column headers
            $headers = !empty($rows[0]) ? array_keys($rows[0]) : [];

            if (isset($dbInfo['displayHeader']['active']) && $dbInfo['displayHeader']['active'] == true) {
                $mapper = !empty($dbInfo['displayHeader']['mapper']) ? $dbInfo['displayHeader']['mapper'] : [];

                if (!empty($headers)) {
                    foreach ($headers as $aHeaderKey => $aHeader) {
                        $aHeaderText = array_key_exists($aHeader, $mapper) ? $mapper[$aHeader] : $aHeader;

                        $headers[$aHeaderKey] = $aHeaderText;
                    }
                }
            }
        } else {
            $headers = [];
            $rows = [];
        }

        // form the return
        return [
            'headers'           => $headers,
            'rows'              => $rows,
            'number_of_results' => (int) $resultNumber,
            'total_pages'       => $pagesNumber,
        ];
    }
    
    /**
     * @param $dbInfo
     * @param $query
     * @param $currentPage
     * @param $perPage
     * @return array
     * @throws \Exception
     */
    private function getDataFromMongo($dbInfo, $query, $currentPage, $perPage)
    {
        $mongoClient = new \MongoClient($dbInfo['server']);
        $database = $mongoClient->selectDB($dbInfo['database']);
        $collection = $database->selectCollection($dbInfo['collection']);

        $searchField = $dbInfo['searchField'];
        $regex = new \MongoRegex("/^{$query}/i");
        $criteria = [$searchField => $regex];
        $results = $collection->find($criteria, $dbInfo['filterResult']);

        if (!$results instanceof \MongoCursor) {
            throw new \Exception('There is an issue getting data from Mongodb');
        }

        $resultNumber = $results->count();
        $start = ($currentPage > 0) ? ($currentPage - 1) * $perPage : 0;
        $rows = $results->limit($perPage)->skip($start);

        /*
         * pagination
         *
         * calculate total pages
         */
        if ($resultNumber < $perPage) {
            $pagesNumber = 1;
        } elseif ($resultNumber > $perPage) {
            if ($resultNumber % $perPage === 0) {
                $pagesNumber = floor($resultNumber / $perPage);
            } else {
                $pagesNumber = floor($resultNumber / $perPage) + 1;
            }
        } else {
            $pagesNumber = $resultNumber / $perPage;
        }

        // form the return
        return [
            'rows' => $rows,
            'number_of_results' => (int) $resultNumber,
            'total_pages' => $pagesNumber,
        ];
    }
    
    /**
     * @return string
     */
    public function getJavascriptAntiBot()
    {
        return $_SESSION['ls_session']['anti_bot'] = Config::getConfig('antiBot');
    }

    /**
     * Calculate the timestamp difference between the time page is loaded
     * and the time searching is started for the first time in seconds
     *
     * @param  $pageLoadedAt
     * @return bool
     */
    public function verifyBotSearched($pageLoadedAt)
    {
        // if searching starts less than start time offset it seems it's a Bot
        return (time() - $pageLoadedAt < Config::getConfig('searchStartTimeOffset')) ? false : true;
    }

    /**
     * @param $dbInfo
     * @param $query
     * @param $currentPage
     * @param $perPage
     *
     * @return array
     * @throws \Exception
     */
    public function renderView($dbInfo, $query, $currentPage, $perPage)
    {
        $result = $this->getData($dbInfo, $query, $currentPage, $perPage);
        $headers = $result['headers'];
        $rows = $result['rows'];

        $DS = DIRECTORY_SEPARATOR;

        $templateName = Config::getConfig('template');
        $templatePath = realpath(__DIR__ . $DS . '..' . $DS . 'templates' . $DS . $templateName);

        if (file_exists($templatePath) !== true) {
            throw new \Exception('Template file not found');
        }

        $html = include_once $templatePath;

        return [
            'html' => $html,
            'number_of_results' => $result['number_of_results'],
            'total_pages'       => $result['total_pages'],
        ];
    }

    /**
     * @return bool
     */
    public function isAJAX()
    {
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'];
        if (!empty($requestedWith) && strtolower($requestedWith) == 'xmlhttprequest') {
            return true;
        } else {
            return false;
        }
    }
}
