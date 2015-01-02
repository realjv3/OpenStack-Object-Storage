<?php

function authenticate($username, $password) {
/*
Use cUrl to get API token and a URL that contains to full path to object storage account. 
cUrl options set: public url to connect to, custom http headers to send (username and password), 
set cUrl to accept any SSL server (SSL probz), 
set cUrl to output http header, set cUrl to output to string instead of stdout;
*/
	$curl = curl_init("https://dal05.objectstorage.softlayer.net/auth/v1.0");

	$curl_options = array (
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

function upload($container, $folder, $file) {
/*
Upload a file to object storage:
Send an http PUT request $x_storage_url/$container/$folder/$file. 
fopen file that the transfer should be read from when uploading and give reference to cUrl. Give anticipated uploaded bytes to cUrl.
Set cUrl to accept any SSL server (SSL probz). Send the custom http header "X-Auth-Token: $x_auth_token" along with the PUT request.
*/

	global $x_auth_token, $x_storage_url;

	if (!is_readable($file)) {
		echo "$file is not readable.\n";
	} else {
			$file_to_upload = fopen($file, "r");
	}

	$curl = curl_init("$x_storage_url/$container/$folder/$file");

	$curl_options = array(
			CURLOPT_PUT => 1,
			CURLOPT_INFILE => $file_to_upload,
			CURLOPT_INFILESIZE => filesize($file),
			CURLOPT_HTTPHEADER => array("X-Auth-Token: $x_auth_token"),
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_VERBOSE => 1
		);

	curl_setopt_array($curl, $curl_options);

	curl_exec($curl);

	curl_close($curl);
}

function download($container, $folder, $file) {
/*
Download a file from object storage:
Send an http GET request (which cUrl defaults to) to $x_storage_url/$container/$folder/$file and have cUrl pipe response into a file.
Set cUrl to accept any SSL server (SSL probz). Send the custom http header "X-Auth-Token: $x_auth_token" along with the GET request.
*/

	global $x_auth_token, $x_storage_url;

	$curl = curl_init("$x_storage_url/$container/$folder/$file");

	$curl_options = array(
			CURLOPT_HTTPHEADER => array("X-Auth-Token: $x_auth_token"),
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FILE => fopen("$file", "w"),
			CURLOPT_VERBOSE => 1
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

function delete($container, $folder, $file) {
/*
Delete a file from object storage:
Send a http DELETE request to $x_storage_url/$container/$folder/$file.
Send the custom http header "X-Auth-Token: $x_auth_token" along with the DELETE request.
*/

	global $x_auth_token, $x_storage_url;

	$curl = curl_init("$x_storage_url/$container/$folder/$file");

	$curl_options = array(
			CURLOPT_CUSTOMREQUEST => "DELETE",
			CURLOPT_HTTPHEADER => array("X-Auth-Token: $x_auth_token"),
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_VERBOSE => 1
		);

	curl_setopt_array($curl, $curl_options);

	curl_exec($curl);

	curl_close($curl);
}

function list_container($container) {

//returns a list of all objects in the container to stdout

	global $x_auth_token, $x_storage_url;

	$curl = curl_init("$x_storage_url/$container");

	$curl_options = array(
			CURLOPT_HTTPHEADER => array("X-Auth-Token: $x_auth_token"),
			CURLOPT_SSL_VERIFYPEER => false,
		);

	curl_setopt_array($curl, $curl_options);

	curl_exec($curl);

	curl_close($curl);
}

?>