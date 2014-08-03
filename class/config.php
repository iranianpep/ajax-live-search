<?php

if(count(get_included_files()) === 1)
    exit("Direct access not permitted.");

class Config
{
    // ***** Database ***** //
    const HOST = 'localhost';
    const DATABASE = 'your_database';
    const USERNAME = 'your_username';
    const PASS = 'your_pass';

    // ***** Table ***** //
    const USER_TABLE = 'your_table';
    const SEARCH_COLUMN = 'first_name';

    // ***** Form ***** //
    // This must be the same as form_anti_bot in script.min.js or script.js
    const ANTI_BOT = "Ehsan's guard";

    // Assigning more than 3 seconds is not recommended
    const SEARCH_START_TIME_OFFSET = 3;

    // ***** Search Input ***** //
    const MAX_INPUT_LENGTH = 20;
}