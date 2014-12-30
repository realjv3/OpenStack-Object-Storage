<?php

require_once "functions.php";

//Authenticate function returns array containing $x_auth_token and $X_storage_url.
//These 2 key/value pairs are extracted into variables for use in further object storage file operations.

$auth_data = authenticate("SLOS292387-2:SL292387", "9f7586a9205d55cbf3d7a808f4693b8d637ef3a94984178869567671e391c9ec");

extract($auth_data);

download("Backups", "test1", "file2.txt");

list_container("Backups");

?>