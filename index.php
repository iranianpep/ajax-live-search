<?php
    file_exists(__DIR__.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'Handler.php') ? require_once(__DIR__.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'Handler.php') : die('There is no such a file: Handler.php');
    file_exists(__DIR__.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'Config.php') ? require_once(__DIR__.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.'Config.php') : die('There is no such a file: Config.php');

    use AjaxLiveSearch\core\Config;
    use AjaxLiveSearch\core\Handler;

if (session_id() == '') {
    session_start();
}

// For debugging. You can get rid of these two lines safely
//    error_reporting(E_ALL);
//    ini_set('display_errors', 1);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href='http://fonts.googleapis.com/css?family=Quattrocento+Sans:400,400italic,700,700italic' rel='stylesheet' type='text/css'>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <meta name="description"
          content="AJAX Live Search is a PHP search form that similar to Google Autocomplete feature displays the result as you type">
    <meta name="keywords"
          content="Ajax Live Search, Autocomplete, Auto Suggest, PHP, HTML, CSS, jQuery, JavaScript, search form, MySQL, web component, responsive">
    <meta name="author" content="Ehsan Abbasi">

    <title>AJAX Live Search</title>

    <!-- Live Search Styles -->
    <link rel="stylesheet" href="css/fontello.css">
    <link rel="stylesheet" href="css/animation.css">
    <!--[if IE 7]>
    <link rel="stylesheet" href="css/fontello-ie7.css">
    <![endif]-->
    <link rel="stylesheet" type="text/css" href="css/style.min.css">
</head>
<body>

<!-- Search Form Demo -->
<div class="ls_container">

    <!-- Search Form -->
    <form accept-charset="UTF-8" class="search" id="ls_form" name="ls_form">
        <?php
            // Set javascript anti bot value in the session
            Handler::getJavascriptAntiBot();
        ?>
        <input type="hidden" name="ls_anti_bot" id="ls_anti_bot" value="">
        <input type="hidden" name="ls_token" id="ls_token" value="<?php echo Handler::getToken(); ?>">
        <input type="hidden" name="ls_page_loaded_at" id="ls_page_loaded_at" value="<?php echo time(); ?>">
        <input type="hidden" name="ls_current_page" id="ls_current_page" value="1">
        <input type="text" name="ls_query" id="ls_query" placeholder="Type to start search (e.g., Chris, 你好, محمد)" autocomplete="off" maxlength="<?php echo Config::getConfig('maxInputLength'); ?>">

        <!-- Result -->
        <div id="ls_result_div">
            <div id="ls_result_main">
                <table>
                    <tbody>

                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div id="ls_result_footer">
                <div class="col page_limit">
                    <select id="ls_items_per_page" name="ls_items_per_page">
                        <option value="5" selected>5</option>
                        <option value="10">10</option>
                        <option value="0">All</option>
                    </select>
                </div>
                <div class="col navigation">
                    <i class="icon-left-circle arrow" id="ls_previous_page"></i>
                </div>
                <div class="col navigation pagination">
                    <label id="ls_current_page_lbl">1</label> / <label id="ls_last_page_lbl"></label>
                </div>
                <div class="col navigation">
                    <i class="icon-right-circle arrow" id="ls_next_page"></i>
                </div>

            </div>

        </div>

    </form>

</div>
<!-- /Search Form Demo -->

<!-- Placed at the end of the document so the pages load faster -->
<script src="js/jquery-1.11.1.min.js"></script>

<!-- Live Search Script -->
<script type="text/javascript" src="js/script.min.js"></script>

</body>
</html>