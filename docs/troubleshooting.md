- In case you get error message: 'Something went wrong. Please refresh the page.', in Chrome right-click and choose `Inspect`. Then in `Network` tab click on `process_livesearch.php` and check the `Response`. For example:
```
SQLSTATE[42000] [1049] Unknown database 'db_name'
```
Means that you have not specified the database name in the config file properly.