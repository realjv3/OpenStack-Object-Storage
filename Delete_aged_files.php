<?php

// On the first of every month, delete backups from month before last

require_once 'functions.php';
$auth_data = authenticate("SET ME", "SET ME");

extract($auth_data);
//get a file list
$curl = curl_init("$x_storage_url/Backups");

$curl_options = array(
		CURLOPT_HTTPHEADER => array("X-Auth-Token: $x_auth_token"),
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_RETURNTRANSFER => true
	);

curl_setopt_array($curl, $curl_options);

$dir_contents = curl_exec($curl);

if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 204) {
	echo "Container is empty.\n";
}

curl_close($curl);

$dir_contents = explode("\n", $dir_contents);
array_shift($dir_contents); //removing Images1 element
array_pop($dir_contents);	//removing blank element
array_pop($dir_contents);	//removing test element
array_pop($dir_contents);	//removing test/file element

//check each file for Last-Modified and delete if Last-Modified is from month before last
while ($dir_contents) {

	$file = array_shift($dir_contents);
	$curl = curl_init("$x_storage_url/Backups/$file");

	$curl_options = array(
			CURLOPT_HTTPHEADER => array("X-Auth-Token: $x_auth_token"),
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => 1,
			CURLOPT_NOBODY => 1
		);

	curl_setopt_array($curl, $curl_options);
	$http_header = curl_exec($curl);
	curl_close($curl);

	$http_header = explode("\n", $http_header);
	$datetime = substr($http_header[3], 14);
	$unixtime = strtotime($datetime);
	$monthmodified = date('m', $unixtime);
	$currentmonth = date('m');
	if ($currentmonth == 1 AND $monthmodified == 11) {
		delete("Backups", $file);
	}
	if ($currentmonth == 2 AND $monthmodified == 12) {
		delete("Backups", $file);
	}
	if ($monthmodified <= ($currentmonth - 2)) {
		delete("Backups", $file);
	}
}	
?>