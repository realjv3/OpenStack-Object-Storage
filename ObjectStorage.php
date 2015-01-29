<?php

/*
This script is ran once daily at 12am to copy daily server backups to Object Storage.

-authenticate function uses private url when inside Softlayer network, uses public API endpint url when outside; 
-filesizes are mostly determined by shell commands instead of filesize() b/c the function doesn't work on files larger than 2GB; 
-on cUrl uploads, additional http header Content-Length is passed because curlopt_fileinsize isn't doing it for some reason on private url, public it works ok; 
-set path to backups set to 
*/

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

$dir_contents = scandir("D:");	//reads contents of current directory into array
array_shift($dir_contents);		//removes the . and the .. elements from beginning of array
array_shift($dir_contents);

//upload files that are < 5GB and modified during last 12 hours

while ($dir_contents) {
	$current_file = array_shift($dir_contents);
	
	$filesize = shell_exec('for %I in (' . "D:/$current_file" . ') do @echo %~zI'); //using a shell command to get bytes b/c filesize() doesn't work > 2GB

	if ($filesize < 5000000000) {									//if filesize less than 5GB
		if (filemtime("D:/$current_file") >= (time() - 43200) ) {	//if file modified within last 12 hours
			$little_files[] = "D:/$current_file";
		}
	} else if ($filesize > 5000000000 AND filemtime("D:/$current_file") >= (time() - 43200)){
		$big_files[] = "D:/$current_file";
	}
}

if (count(@$little_files) == 0) {
	die("No recent files to upload.\n");
} else {
	upload("Backups", "Images1", $little_files);
}

if (count(@$big_files) == 0) {

	die("No recent big files to upload.\n");

} else {

	foreach ($big_files as $big_file) {

		$segments_array = filesplit($big_file, 4900);	//split into 4.9GB segments
		big_file_upload("Backups", "Images1", $segments_array);	//upload the segments & their json manifest
	}
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

?>