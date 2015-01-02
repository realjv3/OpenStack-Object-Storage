<?php

require_once "functions.php";

//Authenticate function returns array containing $x_auth_token and $X_storage_url.
//These 2 key/value pairs are extracted into variables for use in further object storage file operations.

$auth_data = authenticate("SET ME", "SET ME");

extract($auth_data);

// download("Backups", "test1", "file2.txt");

delete("Backups", "test1", "file3.txt");

// upload("Backups", "test1", ".gitignore");

// list_container("Backups");

?>