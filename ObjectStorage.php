<?php

//This script is ran once daily at 5am to copy daily backups to Object Storage.

require_once "functions.php";

//Authenticate function returns array containing $x_auth_token and $X_storage_url.
//These 2 key/value pairs are extracted into variables for use in further object storage file operations.

$auth_data = authenticate("SET ME", "SET ME");

extract($auth_data);

/*
This section is setup to meet my specific needs, which are to copy daily backups to Object Storage and do some cleanup.
1.) upload files < 5GB, modified within last 12 hours to Object Storage
2.) split files larger than 5GB into 4.9GB segments, upload segments and manifest
3.) delete segment & manifest files from local storage
4.) on the first of every month, delete backups from month before last 
*/


$dir_contents = scandir(".");	//reads contents of current directory into array
array_shift($dir_contents);		//removes the . and the .. elements from beginning of array
array_shift($dir_contents);

//upload files that are < 5GB and modified during last 12 hours

while ($dir_contents) {
	$current_file = array_shift($dir_contents);
	echo "Uploading $current_file.\n";
	$filesize = shell_exec('for %I in (' . $current_file . ') do @echo %~zI'); //using a shell command to get bytes b/c filesize() doesn't work > 2GB

	if ($filesize < 5000000000) {
		if (filemtime($current_file) >= (time() - 100000) ) {
			upload("Backups", "test1", "$current_file");
		}
	} else {
		$big_files[] = $current_file;
	}
}

//split files larger than 5GB into 4.9GB segments, upload segments and manifest

while ($big_files) {
	$current_file = array_shift($big_files);
	filesplit("C:/test/$current_file", 4900);
	segment_upload("Backups", "test1", "$current_file");
}

//delete segment & manifest files from local storage

$dir = opendir("C:/test");
while ($file = readdir($dir)) {
	if (fnmatch("$file", "*.*.*") or fnmatch("$file", "*.json")) {
		unlink("$file");
	}
}
closedir();
// delete("Backups", "test1", "ObjectStorage.php");
// delete("Backups", "test1", "curloutput.txt");
// delete("Backups", "test1", "functions.php");
//list_container("Backups");

?>