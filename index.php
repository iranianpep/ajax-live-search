<?php

$DS = DIRECTORY_SEPARATOR;
file_exists(__DIR__ . $DS . 'core' . $DS . 'Handler.php') ? require_once __DIR__ . $DS . 'core' . $DS . 'Handler.php' : die('Handler.php not found');
file_exists(__DIR__ . $DS . 'core' . $DS . 'Config.php') ? require_once __DIR__ . $DS . 'core' . $DS . 'Config.php' : die('Config.php not found');

use AjaxLiveSearch\core\Config;
use AjaxLiveSearch\core\Handler;

if (session_id() == '') {
    session_start();
}

    $handler = new Handler();
    $handler->getJavascriptAntiBot();
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
    <link rel="stylesheet" type="text/css" href="css/ajaxlivesearch.min.css">
</head>
<body>

<!-- Search Form Demo -->
<div style="clear: both">
    <input type="text" class='mySearch' id="ls_query" placeholder="Type to start searching ...">
</div>
<!-- /Search Form Demo -->

<!-- Placed at the end of the document so the pages load faster -->
<script src="js/jquery-1.11.1.min.js"></script>

<!-- Live Search Script -->
<script type="text/javascript" src="js/ajaxlivesearch.min.js"></script>

<script>
jQuery(document).ready(function(){
    jQuery(".mySearch").ajaxlivesearch({
        loaded_at: <?php echo time(); ?>,
        token: <?php echo "'" . $handler->getToken() . "'"; ?>,
        max_input: <?php echo Config::getConfig('maxInputLength'); ?>,
        onResultClick: function(e, data) {
            // get the index 0 (first column) value
            var selectedOne = jQuery(data.selected).find('td').eq('0').text();

            // set the input value
            jQuery('#ls_query').val(selectedOne);

            // hide the result
            jQuery("#ls_query").trigger('ajaxlivesearch:hide_result');
        },
        onResultEnter: function(e, data) {
            // do whatever you want
            // jQuery("#ls_query").trigger('ajaxlivesearch:search', {query: 'test'});
        },
        onAjaxComplete: function(e, data) {

        }
    });
})
</script>

</body>
</html>
