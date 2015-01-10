<?php

require_once "functions.php";

//Authenticate function returns array containing $x_auth_token and $X_storage_url.
//These 2 key/value pairs are extracted into variables for use in further object storage file operations.

$auth_data = authenticate("SET ME", "SET ME");

extract($auth_data);

download("Backups", "test1", "pwhe8.exe");

// delete("Segments", "", "pwhe8.exe.002");

// filesplit("C:/users/john/downloads/pwhe8.exe", 10);

// segment_upload("Backups", "test1", "pwhe8.exe");

// list_container("Segments");

?>