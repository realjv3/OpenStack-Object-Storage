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

$dir_contents = scandir("C:/Users/John/Downloads");	//reads contents of current directory into array
array_shift($dir_contents);		//removes the . and the .. elements from beginning of array
array_shift($dir_contents);

//upload files that are < 5GB and modified during last 16 hours

while ($dir_contents) {
	$current_file = array_shift($dir_contents);
	
	$filesize = shell_exec('for %I in (' . "C:/Users/John/Downloads/$current_file" . ') do @echo %~zI'); //using a shell command to get bytes b/c filesize() doesn't work > 2GB

	if ($filesize < 5000000000) {									//if filesize less than 5GB
		if (filemtime("C:/Users/John/Downloads/$current_file") >= (time() - 21600) ) {	//if file modified within last 6 hours
			$little_files[] = $current_file;
			echo "Uploading $current_file.\n";
			upload("Backups", "test1", "$current_file");
		}
	} else if ($filesize > 5000000000 AND filemtime("C:/Users/John/Downloads/$current_file") >= (time() - 21600)){
		$big_files[] = $current_file;
	}
}
if (@!$little_files) {die("No recent files to upload.\n");}

//split files larger than 5GB into 4.9GB segments, upload segments and manifest

if ($big_files) {

	while ($big_files) {
		$current_file = array_shift($big_files);
		filesplit("C:/Users/John/Downloads/$current_file", 4900);
		segment_upload("Backups", "Images1", "$current_file");
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