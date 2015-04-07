<?php

//This script is ran once daily at 12am to copy daily backups to Object Storage.

require_once "functions.php";

//Authenticate function returns array containing $x_auth_token and $X_storage_url.
//These 2 key/value pairs are extracted into variables for use in further object storage file operations.

$auth_data = authenticate("SET ME", "SET ME");

extract($auth_data);

/*
This section is setup to meet my specific needs, which are to copy daily backups to Object Storage and do some cleanup.
1.) upload files < 5GB, modified within last 6 hours to Object Storage
2.) split files larger than 5GB into 4.9GB segments, upload segments and manifest
3.) delete segment & manifest files from local directory
*/

//upload files by name

$files = array( "jbsql01_C_Drive010.v2i", "jbsql01_D_Drive010.v2i", "jbsql01_M_Drive010.v2i", "jbremote01_C_Drive010.v2i");		 //add files to array that you want to upload

while ($files) {

	$current_file = array_shift($files);
	
	$filesize = shell_exec('for %I in (J:/Images1/' . $current_file . ') do @echo %~zI'); //using a shell command to get bytes b/c filesize() doesn't work > 2GB
	$filesize = substr($filesize, 0, -1);	//removing line break from end of string
	
	if ($filesize < 5000000000) {									//if filesize less than 5GB
			$little_files[] = $current_file;
			upload("Backups", "Images1", "$current_file");
	} else if ($filesize > 5000000000) {
			$big_files[] = $current_file;
	}
}

if (@!$little_files) echo "No little files to upload.\n";

//split files larger than 5GB into 4.9GB s egments, upload segments and manifest

if ($big_files) {

	while ($big_files) {
		$current_file = array_shift($big_files);
		filesplit("J:/Images1/$current_file", 2000);
		segment_upload("Backups", "Images1", "$current_file");
		sleep(10);
	}

//cleanup: delete segment & manifest files from local directory

	$dir = opendir(".");
	while ($file = readdir($dir)) {
		if (fnmatch("*.*.*", "$file") or fnmatch("*.json", "$file")) {
			@unlink("$file");
			echo "$file deleted from local storage.\n";
		}
	}
	closedir();
} else {
	echo "No recent big files to upload.\n";
}

?>