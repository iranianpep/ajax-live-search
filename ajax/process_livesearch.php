<?php
    use AjaxLiveSearch\core\Handler;

    header('Access-Control-Allow-Origin: http://ajaxlivesearch.com');
    header('Access-Control-Allow-Methods: *');
    header('Content-Type: application/json');

    file_exists(realpath(__DIR__ .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'Handler.php')) ? require_once(realpath(__DIR__ .DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'Handler.php')) : die('There is no such a file: Handler.php');

    // 1. Validate all inputs
    $errors = Handler::validateInput($_POST);

    if (!empty($errors)) {
        // Required inputs are not provided
        Handler::formResponse('failed', "Error: Required or invalid inputs: " . implode(',', $errors));
    }

    // 2. A layer of security against those Bots that submit a form quickly
    if (!Handler::verifyBotSearched($_POST['ls_page_loaded_at'])) {
        // Searching is started sooner than the search start time offset
        Handler::formResponse('failed', 'Error: You are too fast, or this is a Bot. Please search now.');
    }

    // 3. Verify the token - CSRF protection
    if (!Handler::verifySessionValue('token', $_POST['ls_token']) ||
        !Handler::verifySessionValue('anti_bot', $_POST['ls_anti_bot'])
    ) {
        // Tokens are not matched
        Handler::formResponse('failed', 'Error: Please refresh the page. It seems that your session is expired.');
    }

    // 4. Start looking for the query
    $result = json_encode(Handler::getResult(
        $_POST['ls_query'],
        (int)$_POST['ls_current_page'],
        (int)$_POST['ls_items_per_page']
    ));

    // 5. Return the result
    Handler::formResponse('success', 'Successful request', $result);
