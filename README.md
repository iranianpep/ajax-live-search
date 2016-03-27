# [AJAX Live Search](http://ajaxlivesearch.com)

AJAX Live Search is a jQuery plugin / PHP search form that searches and displays the result as you type similar to Google Autocomplete feature.

## Demo

[Check it it in action.](http://ajaxlivesearch.com)<br>
<img src='http://ajaxlivesearch.com/img/ajax_demo.gif'>

## Browser Support

![IE](https://raw.github.com/alrra/browser-logos/master/internet-explorer/internet-explorer_48x48.png) | ![Chrome](https://raw.github.com/alrra/browser-logos/master/chrome/chrome_48x48.png) | ![Firefox](https://raw.github.com/alrra/browser-logos/master/firefox/firefox_48x48.png) | ![Opera](https://raw.github.com/alrra/browser-logos/master/opera/opera_48x48.png) | ![Safari](https://raw.github.com/alrra/browser-logos/master/safari/safari_48x48.png)
--- | --- | --- | --- | --- |
IE 8+ ✔ | Chrome ✔ | Firefox ✔ | Opera ✔ | Safari ✔ |

## Getting started with Ajax Live Search

Assuming you have this text field:
`<input type="text" class='mySearch' id="ls_query">`

1. Copy the folders including `ajax`, `core`, `css`, `font`, `img` and `js` to your project.

2. Specify the required configurations specially database configurations in `Config.php`. The file is located in `core` folder and contains back-end settings for the plugin. Check PHP Configs table for more details.

3. Include `ajaxlivesearch.min.js` or `ajaxlivesearch.js` located in `js` folder and `ajaxlivesearch.min.css` or `ajaxlivesearch.css` located in `css` in your project.

4. Change the url for `Access-Control-Allow-Origin header` in `process_livesearch.php` that is located in `ajax` folder.

5. Make sure php files: `Handler.php` and `Config.php` are included in the php page and you have these lines at the very top of the file (Check `index.php`):

	```
	if (session_id() == '') {
    	session_start();
	}

	Handler::getJavascriptAntiBot();
	$token = Handler::getToken();
	$time = time();
	$maxInputLength = Config::getConfig('maxInputLength');
	```
	
6. Lastly, hook the plugin to the text field and pass required options (loaded_at & token):

	```
jQuery("#ls_query").ajaxlivesearch({
        loaded_at: <?php echo $time; ?>,
        token: <?php echo "'" . $token . "'"; ?>,
        maxInput: <?php echo $maxInputLength; ?>,
    });
	```

## jQuery Options
<table width='100%'>
<thead>
<tr>
<th>Name</th>
<th>Type</th>
<th>Required</th>
<th>Description</th>
</tr>
</thead>
<tbody>
<tr>
<td>loaded_at</td>
<td>Integer</td>
<td>Yes</td>
<td>This is used to prevent bots from searching.</td>
</tr>
<tr>
<td>token</td>
<td>String</td>
<td>Yes</td>
<td>This is used to prevent CSRF attack.</td>
</tr>
<tr>
<td>url</td>
<td>String</td>
<td>No</td>
<td>Default: ajax/process_livesearch.php.</td>
</tr>
<tr>
<td>cache</td>
<td>Boolean</td>
<td>No</td>
<td>This refers to Ajax request caching. Default: false</td>
</tr>
<tr>
<td>form_anti_bot</td>
<td>String</td>
<td>No</td>
<td>Default: ajaxlivesearch_guard</td>
</tr>
<tr>
<td>slide_speed</td>
<td>String</td>
<td>No</td>
<td>Default: fast</td>
</tr>
<tr>
<td>type_delay</td>
<td>Integer</td>
<td>No</td>
<td>Default: 350</td>
</tr>
<tr>
<td>max_input</td>
<td>Integer</td>
<td>No</td>
<td>Default: 20</td>
</tr>
<tr>
<td>min_chars_to_search</td>
<td>Integer</td>
<td>No</td>
<td>Minimum characters length to start searching. Default: 0</td>
</tr>
<tr>
<td>page_ranges</td>
<td>Array</td>
<td>No</td>
<td>Default: [0, 5, 10]</td>
</tr>
<tr>
<td>page_range_default</td>
<td>Integer</td>
<td>No</td>
<td>Default: 5</td>
</tr>
<tr>
<td>form_anti_bot_class</td>
<td>String</td>
<td>No</td>
<td>Default: ls_anti_bot</td>
</tr>
<tr>
<td>footer_class</td>
<td>String</td>
<td>No</td>
<td>Default: ls_result_footer</td>
</tr>
<tr>
<td>next_page_class</td>
<td>String</td>
<td>No</td>
<td>Default: ls_next_page</td>
</tr>
<tr>
<td>previous_page_class</td>
<td>String</td>
<td>No</td>
<td>Default: ls_previous_page</td>
</tr>
<tr>
<td>page_limit</td>
<td>String</td>
<td>No</td>
<td>Default: page_limit</td>
</tr>
<tr>
<td>result_wrapper_class</td>
<td>String</td>
<td>No</td>
<td>Default: ls_result_div</td>
</tr>
<tr>
<td>result_class</td>
<td>String</td>
<td>No</td>
<td>Default: ls_result_main</td>
</tr>
<tr>
<td>container_class</td>
<td>String</td>
<td>No</td>
<td>Default: ls_container</td>
</tr>
<tr>
<td>pagination_class</td>
<td>String</td>
<td>No</td>
<td>Default: pagination</td>
</tr>
<tr>
<td>form_class</td>
<td>String</td>
<td>No</td>
<td>Default: search</td>
</tr>
<tr>
<td>loaded_at_class</td>
<td>String</td>
<td>No</td>
<td>Default: ls_page_loaded_at</td>
</tr>
<tr>
<td>token_class</td>
<td>String</td>
<td>No</td>
<td>Default: ls_token</td>
</tr>
<tr>
<td>current_page_hidden_class</td>
<td>String</td>
<td>No</td>
<td>Default: ls_current_page</td>
</tr>
<tr>
<td>current_page_lbl_class</td>
<td>String</td>
<td>No</td>
<td>Default: ls_current_page_lbl</td>
</tr>
<tr>
<td>last_page_lbl_class</td>
<td>String</td>
<td>No</td>
<td>Default: ls_last_page_lbl</td>
</tr>
<tr>
<td>total_page_lbl_class</td>
<td>String</td>
<td>No</td>
<td>Default: ls_last_page_lbl</td>
</tr>
<tr>
<td>page_range_class</td>
<td>String</td>
<td>No</td>
<td>Default: ls_items_per_page</td>
</tr>
<tr>
<td>navigation_class</td>
<td>String</td>
<td>No</td>
<td>Default: navigation</td>
</tr>
<tr>
<td>arrow_class</td>
<td>String</td>
<td>No</td>
<td>Default: arrow</td>
</tr>
</tbody>
</table>

## Custom Event
<table width='100%'>
<thead>
<tr>
<th>Name</th>
</tr>
</thead>
<tbody>
<tr>
<td>onResultClick</td>
</tr>
<tr>
<td>onResultEnter</td>
</tr>
<tr>
<td>onAjaxComplete</td>
</tr>
</tbody>
</table>

Example:

```
jQuery(".mySearch").ajaxlivesearch({
        loaded_at: <?php echo $time; ?>,
        token: <?php echo "'" . $token . "'"; ?>,
        maxInput: <?php echo $maxInputLength; ?>,
        onResultClick: function(e, data) {
            // get the index 1 (second column) value
            var selectedOne = jQuery(data.selected).find('td').eq('1').text();

            // set the input value
            jQuery('.mySearch').val(selectedOne);

            // hide the result
            jQuery(".mySearch").trigger('ajaxlivesearch:hide_result');
        },
        onResultEnter: function(e, data) {
            // do whatever you want
            // jQuery(".mySearch").trigger('ajaxlivesearch:search', {query: 'test'});
        },
        onAjaxComplete: function(e, data) {
            // do whatever you want
        }
    });
```
## Custom Trigger
<table width='100%'>
<thead>
<tr>
<th>Name</th>
</tr>
</thead>
<tbody>
<tr>
<td>ajaxlivesearch:hide_result</td>
</tr>
<tr>
<td>ajaxlivesearch:search</td>
</tr>
</tbody>
</table>

## PHP Configurations
<table width='100%'>
<thead>
<tr>
<th>Name</th>
<th>Type</th>
<th>Required</th>
<th>Description</th>
</tr>
</thead>
<tbody>
<tr>
<td>dataSources</td>
<td>Array</td>
<td>Yes</td>
<td>Data source for each search text field. Keys are refering to the field HTML id. Currently MySQL and mongoDB (this is in beta) are supported.<br><br>
MySQL data source configs:
<table width='100%'>
<thead>
<tr>
<th>Name</th>
<th>Type</th>
<th>Required</th>
<th>Description</th>
</tr>
</thead>
<tbody>
<tr>
<td>host</td>
<td>String</td>
<td>Yes</td>
<td>MySQL database host. It usually is 'localhost'.</td>
</tr>
<tr>
<td>database</td>
<td>String</td>
<td>Yes</td>
<td>MySQL database name.</td>
</tr>
<tr>
<td>username</td>
<td>String</td>
<td>Yes</td>
<td>MySQL database username.</td>
</tr>
<tr>
<td>pass</td>
<td>String</td>
<td>Yes</td>
<td>MySQL database username password.</td>
</tr>
<tr>
<td>table</td>
<td>String</td>
<td>Yes</td>
<td>MySQL database table that the live search searches in.</td>
</tr>
<tr>
<td>searchColumns</td>
<td>Array</td>
<td>Yes</td>
<td>Search columns that the live search searches in. It can be one or many. e.g. array('column_name_1', 'column_name_2')</td>
</tr>
<tr>
<td>orderBy</td>
<td>String</td>
<td>No</td>
<td>Column that the result is ordered based in it.</td>
</tr>
<tr>
<td>orderDirection</td>
<td>String</td>
<td>No</td>
<td>Order direction: 'ASC' or 'DESC' for 'orderBy'. Default value: ASC</td>
</tr>
<tr>
<td>filterResult</td>
<td>Array</td>
<td>No</td>
<td>Columns that need to be in the result. If it is empty all the columns will be returned.</td>
</tr>
<tr>
<td>comparisonOperator</td>
<td>String</td>
<td>Yes</td>
<td>Search query comparison operator. Possible values for comparison operators are: 'LIKE' and '='.</td>
</tr>
<tr>
<td>searchPattern</td>
<td>String</td>
<td>Yes</td>
<td>This is used to specify how the query is searched. possible values are: `q`, `*q`, `q*`, `*q*`.</td>
</tr>
<tr>
<td>caseSensitive</td>
<td>String</td>
<td>Yes</td>
<td>Search query case sensitivity</td>
</tr>
<tr>
<td>maxResult</td>
<td>Integer</td>
<td>No</td>
<td>This is used to limit the maximum number of result.</td>
</tr>
<tr>
<td>displayHeader</td>
<td>Array</td>
<td>No</td>
<td>This is used to display or hide the header in the result. If 'active' is set to true header is displayed. Also, it is possible to map columns to different titles.</td>
</tr>
<tr>
<td>columnClass</td>
<td>Array</td>
<td>No</td>
<td>It is possible to map columns to custom css class(es). Use ls_hide to hide column from table display.</td>
</tr>
<tr>
<td>type</td>
<td>String</td>
<td>Yes</td>
<td>Type of the datasource. Currently possible values are: 'mysql' or 'mongo'.</td>
</tr>
</tbody>
</table>
</td>
</tr>
<tr>
<td>antiBot</td>
<td>String</td>
<td>Yes</td>
<td>This is used as a security technique to prevent form submissions from those bots that do not use JavaScript. In this technique, a hidden field is populated using jQuery with this value. It can have any value, but it MUST be the same as `form_anti_bot` option passed to the jQuery plugin. By default it is set to `ajaxlivesearch_guard`.</td>
</tr>
<tr>
<td>searchStartTimeOffset</td>
<td>Integer</td>
<td>Yes</td>
<td>This is used for another security technique against bots. Some bots immediately submit a form once the page is finished loading. However, for human beings it takes more time to fill a field. By default this parameter is set to 3 seconds. Assigning more than 3 seconds is not recommended.</td>
</tr>
<tr>
<td>maxInputLength</td>
<td>Integer</td>
<td>Yes</td>
<td>This specifies the maximum length of characters in search field.</td>
</tr>
</tbody>
</table>

## License

[MIT License](https://github.com/iranianpep/ajax-live-search/blob/master/LICENSE.txt)
