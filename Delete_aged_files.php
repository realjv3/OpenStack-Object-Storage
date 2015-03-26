<?php

// On the first of every month, delete backups from month before last

require_once 'functions.php';
$auth_data = authenticate("SET ME", "SET ME");

extract($auth_data);

$paths_to_delete_from = array("Backups/Images1", "Segments");	//set paths to delete from

for ($i=0; $i < count($paths_to_delete_from) ; $i++) { 

	$folder = '';
	$dir_contents = '';
	$path = $paths_to_delete_from[$i];

	//checking if path is container/folder, if yes, storing in $container and $folder variables
	//saving string returned from list_container(), then exploding string into array

	if (strpos($path, "/") != FALSE) {	

		$a = explode("/", $path);
		$container = array_shift($a);
		$folder = array_shift($a);
		$dir_contents .= list_container("$container?path=$folder");
	} else {
		$container = $path;
		$dir_contents .= list_container("$path");
	}

	$dir_contents = explode("\n", $dir_contents);

	//loop iteratates through $dir_contents and uses http head request to get modified dates
	//if file was modified month before last, it gets deleted

	foreach ($dir_contents as $key => $file) {		
				
		//removing "folder/" from file names in the array, if a folder is there

		$pos = strpos($file, "/");
		if ($pos != FALSE) $file = substr($file, $pos + 1);

		//removing empty values from end of $dir_contents array, breaking b/c otherwise loop will iterate one extra time

		if ($file == '') {
			unset($dir_contents[$key]);
			break;
		}	

		//check each file for Last-Modified

		if (strlen($folder) > 0) {
			$path = "$container/$folder/$file";
		} else {
			$path = "$container/$file";
		}
		$curl = curl_init("$x_storage_url/$path");
		 
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
		$monthmodified = ltrim(date('m', $unixtime), '0');
		$currentmonth = ltrim(date('m'), '0');

		//delete if Last-Modified is from month before last

		if ($currentmonth == 1 AND $monthmodified == 11) {
			delete($path);
		}
		if ($currentmonth == 2 AND $monthmodified == 12) {
			delete($path);
		}
		if ($monthmodified <= ($currentmonth - 2)) {
			delete($path);
		}	
	}	
}		
?>