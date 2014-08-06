# [AJAX Live Search](http://ajaxlivesearch.com)

AJAX Live Search is a PHP search form that similar to Google Autocomplete feature displays the result as you type.

## Demo

[Check it live](http://ajaxlivesearch.com).

## Browser Support

![IE](https://raw.github.com/alrra/browser-logos/master/internet-explorer/internet-explorer_48x48.png) | ![Chrome](https://raw.github.com/alrra/browser-logos/master/chrome/chrome_48x48.png) | ![Firefox](https://raw.github.com/alrra/browser-logos/master/firefox/firefox_48x48.png) | ![Opera](https://raw.github.com/alrra/browser-logos/master/opera/opera_48x48.png) | ![Safari](https://raw.github.com/alrra/browser-logos/master/safari/safari_48x48.png)
--- | --- | --- | --- | --- |
IE 8+ ✔ | Chrome ✔ | Firefox ✔ | Opera ✔ | Safari ✔ |

## How to Install

1. Copy the folders including `ajax`, `class`, `css`, `font`, `img` and `js` folders to your project.

2. Open `index.php` and copy `div` with the class name `ls_container` somewhere in your page. Also do not forget to include links to CSS and JavaScript files including `style.min.css`, `fontello.css`, `animation.css`, `fontello-ie7.css`, `script.min.js` and `jquery-1.11.1.min.js`. Also do not forget to copy all the PHP codes from the top of `index.php` to your project.

3. `config.php` that is located in `class` folder contains all the settings for AJAX Live Search:
	- `HOST`: Hostname of your database that is usually `localhost`.
	- `DATABASE`: Name of your database.
	- `USERNAME`: The user associated with your database.
	- `PASS`: Password for the user.
	- `USER_TABLE`: Name of the table that you want to be searched.
	- `SEARCH_COLUMN`: Name of the column that you want to be searched.
	- `ANTI_BOT`: This is used in a security technique to prevent form submissions from those bots that do not use JavaScript. In this technique, a hidden field is populated using jQuery with `ANTI_BOT` value. You can set it whatever you want, but it MUST be the same as `ANTI_BOT` value in `script.min.js` file.
	- `SEARCH_START_TIME_OFFSET`: This is for another security technique against bots. Some bots immediately submit a form once the page is finished loading. However, for human beings it takes more time to fill a field. By default this parameter is set to 3 seconds.
	- `MAX_INPUT_LENGTH`: This specifies the maximum length of characters in search field.

4. `script.min.js` or `script.js` that is located in js folder contains all the JavaScript (jQuery) settings and functions for AJAX Live Search. Here you should only be worried about `form_anti_bot` value and as you know it MUST be the same as `ANTI_BOT` value in `config.php`.

5. `process_livesearch.php` that is located in ajax folder is responsible for processing requests coming from the search form. Here you just need to set `Access-Control-Allow-Originheader`.

6. Enjoy.

## License

[MIT License](https://github.com/iranianpep/ajax-live-search/blob/master/LICENSE.txt)
