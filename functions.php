<?php

function authenticate($username, $password) {
/*
Use cUrl to get API token and a URL that contains to full path to object storage account. 
cUrl options set: public url to connect to, custom http headers to send (username and password), 
set cUrl to accept any SSL server (https probz), 
set cUrl to output http header, set cUrl to output to string instead of stdout;
*/
	$curl = curl_init();

	$curl_options = array (
		CURLOPT_URL => "https://dal05.objectstorage.softlayer.net/auth/v1.0",
		CURLOPT_HTTPHEADER => array("X-Auth-User: $username", "X-Auth-Key: $password"),
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_HEADER => 1,
		CURLOPT_RETURNTRANSFER => 1
	);

	curl_setopt_array($curl, $curl_options);

	$curl_result = curl_exec($curl);

	curl_close($curl);

/*
Get the API token and url from the returned http header and store them in vars to use in future operations
*/

	$x_auth_token = substr($curl_result, 81, 39);
	$x_storage_url = substr($curl_result, 195, 86);

	return array(
		"x_auth_token" => $x_auth_token, 
		"x_storage_url" => $x_storage_url
	);
}

function download($container, $folder, $file) {
/*
Download a file from object storage
Just send an http GET request (which cUrl defaults to) to $x_storage_url/$container/$folder/$file and have cUrl pipe response into a file.
Send the custom http header "X-Auth-Token: $x_auth_token" along with the GET request.
*/

	global $x_auth_token, $x_storage_url;

	$curl = curl_init();

	$curl_options = array(
			CURLOPT_URL => "$x_storage_url/$container/$folder/$file",
			CURLOPT_HTTPHEADER => array("X-Auth-Token: $x_auth_token"),
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FILE => fopen("$file", "w"),
		);

	curl_setopt_array($curl, $curl_options);

	curl_exec($curl);

	curl_close($curl);

	if (is_readable($file)) {
		echo "Requested file has been downloaded.\n";
	} else {
		echo "There was a problem with the download.\n";
	}
}

function delete() {

	global $x_auth_token, $x_storage_url;
	// curl –X DELETE -i -H "X-Auth-Token: $token" $publicURL/elaine/JingleRocky.jpg
}

function list_container($container) {

//returns a list of all objects in the container to stdout

	global $x_auth_token, $x_storage_url;

	$curl = curl_init();

	$curl_options = array(
			CURLOPT_URL => "$x_storage_url/$container",
			CURLOPT_HTTPHEADER => array("X-Auth-Token: $x_auth_token"),
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HEADER => 1,
		);

	curl_setopt_array($curl, $curl_options);

	curl_exec($curl);

	curl_close($curl);
}

?>