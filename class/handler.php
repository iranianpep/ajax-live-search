<?php

if(count(get_included_files()) === 1)
    exit("Direct access not permitted.");

if(session_id() == '')
    session_start();

file_exists(__DIR__.DIRECTORY_SEPARATOR.'db.php') ? require_once(__DIR__.DIRECTORY_SEPARATOR.'db.php') : die('There is no such a file: db.php');
file_exists(__DIR__.DIRECTORY_SEPARATOR.'config.php') ? require_once(__DIR__.DIRECTORY_SEPARATOR.'config.php') : die('There is no such a file: config.php');

class Handler
{

    /*
     * returns a 32 bits token and resets the old token if exists
     */
    public static function get_token()
    {
        // create a form token to protect against CSRF
        $token = bin2hex(openssl_random_pseudo_bytes(32));
        $_SESSION['ls_session']['token'] = $token;
        return $token;
    }

    /*
     * receives a posted variable and checks it against the same one in the session
     */
    public static function verify_session_value($session_parameter, $session_value)
    {
        $white_list = array('token', 'anti_bot');

        if (in_array($session_parameter, $white_list) && $_SESSION['ls_session'][$session_parameter] === $session_value)
            return true;
        else
            return false;
    }

    /*
     * checks required fields, max length for search input and numbers for pagination
     */
    public static function validate_input($input_array)
    {
        $error = array();

        foreach($input_array as $k => $v)
        {
            if (!isset($v) || (trim($v) == "" && $v != "0") || $v == null)
            {
                array_push($error, $k);
            }
            elseif ($k === 'ls_current_page' || $k === 'ls_items_per_page')
            {
                if ((int) $v < 0)
                    array_push($error, $k);
            }
            elseif ($k === 'ls_query' && strlen($v) > Config::MAX_INPUT_LENGTH)
                array_push($error, $k);
        }

        return $error;
    }

    /*
     * forms the response object including
     * status (success or failed)
     * message
     * result (html result)
     */
    public static function form_response($status, $message, $result = '')
    {
        $css_class = ($status === 'failed') ? 'error' : 'success';

        $message = "<tr><td class='{$css_class}'>{$message}</td></tr>";

        echo json_encode(array('status' => $status, 'message' => $message, 'result' => $result));
    }

    public static function get_result($query, $current_page = 1, $items_per_page = 0)
    {
        // get connection
        $db = DB::getConnection();

        // get the number of total result
        $stmt = $db->prepare('SELECT COUNT(id) FROM ' . Config::USER_TABLE . ' WHERE ' . Config::SEARCH_COLUMN . ' LIKE :query');
        $search_query = $query.'%';
        $stmt->bindParam(':query', $search_query, PDO::PARAM_STR);
        $stmt->execute();
        $number_of_result = $stmt->fetch(PDO::FETCH_COLUMN);

        // initialize variables
        $HTML = '';
        $number_of_pages = 1;

        if ( (int) $number_of_result !== 0)
        {
            if ($items_per_page === 0)
            {
                // show all
                $stmt = $db->prepare('SELECT * FROM ' . Config::USER_TABLE . ' WHERE ' . Config::SEARCH_COLUMN . ' LIKE :query ORDER BY ' .Config::SEARCH_COLUMN);
                $search_query = $query.'%';
                $stmt->bindParam(':query', $search_query, PDO::PARAM_STR);
            }
            else
            {
                /*
                 * pagination
                 *
                 * calculate total pages
                 */
                if ($number_of_result < $items_per_page)
                    $number_of_pages = 1;
                elseif ($number_of_result > $items_per_page)
                    $number_of_pages = floor($number_of_result / $items_per_page) + 1;
                else
                    $number_of_pages = $number_of_result / $items_per_page;

                /*
                 * pagination
                 *
                 * calculate start
                 */
                $start = ($current_page > 0 ) ? ($current_page - 1) * $items_per_page : 0;

                $stmt = $db->prepare('SELECT * FROM ' . Config::USER_TABLE . ' WHERE ' . Config::SEARCH_COLUMN . ' LIKE :query ORDER BY '.Config::SEARCH_COLUMN.' LIMIT ' . $start . ', ' . $items_per_page);
                $search_query = $query.'%';
                $stmt->bindParam(':query', $search_query, PDO::PARAM_STR);
            }

            // run the query and get the result
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // generate HTML
            foreach($results as $result)
            {
                $HTML .= "<tr><td>{$result['first_name']}</td><td>{$result['last_name']}</td><td>{$result['age']}</td><td>{$result['country']}</td></tr>";
            }

        }
        else
        {
            // To prevent XSS prevention convert user input to HTML entities
            $query = htmlentities($query, ENT_NOQUOTES, 'UTF-8');

            // there is no result - return an appropriate message.
            $HTML .= "<tr><td>There is no result for \"{$query}\"</td></tr>";
        }

        // form the return
        return array('html' => $HTML, 'number_of_results' => (int) $number_of_result, 'total_pages' => $number_of_pages);
    }

    public static function get_javascript_anti_bot()
    {
        $_SESSION['ls_session']['anti_bot'] = Config::ANTI_BOT;
        return Config::ANTI_BOT;
    }

    /*
     * Calculate the timestamp difference between the time page is loaded
     * and the time searching is started for the first time in seconds
     */
    public static function verify_bot_searched($page_loaded_at)
    {
        // if searching starts less than start time offset it seems it's a Bot
        return (time() - $page_loaded_at < Config::SEARCH_START_TIME_OFFSET) ? false : true;
    }
}
