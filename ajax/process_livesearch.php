<?php

header('Access-Control-Allow-Origin: http://ajaxlivesearch.com');
header('Access-Control-Allow-Methods: *');
header('Content-Type: application/json');
header_remove('X-Powered-By');

file_exists(realpath(__DIR__ .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'handler.php')) ? require_once(realpath(__DIR__ .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'handler.php')) : die('There is no such a file: handler.php');

// 1. Validate all inputs
$errors = Handler::validate_input($_POST);
if (count($errors) === 0)
{
    // 2. A layer of security against those Bots that submit a form quickly
    if (Handler::verify_bot_searched($_POST['ls_page_loaded_at']))
    {
        // 3. Verify the token - CSRF protection
        if (Handler::verify_session_value('token', $_POST['ls_token']) && Handler::verify_session_value('anti_bot' ,$_POST['ls_anti_bot']))
        {
            // 4. Start looking for the query
            $result = json_encode(Handler::get_result($_POST['ls_query'], (int) $_POST['ls_current_page'], (int) $_POST['ls_items_per_page']));

            // 5. Return the result
            Handler::form_response('success', 'Successful request', $result);
        }
        else
        {
            // Tokens are not matched
            Handler::form_response('failed', 'Error: Please refresh the page. It seems that your session is expired.');
        }
    }
    else
    {
        // Searching is started sooner than the search start time offset
        Handler::form_response('failed', 'Error: You are too fast, or this is a Bot. Please search now.');
    }
}
else
{
    // Required inputs are not provided
    Handler::form_response('failed', "Error: Required or invalid inputs: " . implode(',', $errors));
}