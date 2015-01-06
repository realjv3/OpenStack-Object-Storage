<?php

function br() {
	return (!empty($_SERVER['SERVER_SOFTWARE']))?'<br>':"\n";
}

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

	if (filesize($file) >= 4999999999) {
		die("File size of $file exceeds 5GB, must be split into smaller pieces before uploading." . br());
	}

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

function segment_upload($container, $folder, $file) {
/*
Creation of a static large object is done in several steps. First we divide the content into pieces using filesplit function and upload each piece into a segment object. Then we create a manifest object. We will place the segment objects into the "segments" container and the manifest object into the "Backups" container.
curl –X PUT -i -H "X-Auth-Token: 12345" -T ./piece1
    https://$x_storage_url/segments/terrier-jpg-one
curl –X PUT -i -H "X-Auth-Token: 12345" -T ./piece2
    https://$x_storage_url/segments/terrier-jpg-two
curl –X PUT -i -H "X-Auth-Token: 12345" -T ./piece3
    https://$x_storage_url/segments/terrier-jpg-three

We receive this kind http header back for each uploaded piece:
HTTP/1.1 201 Created
Content-Length: 1000
Etag: 00b046c9d74c3e8f93b320c5e5fdc2c3

Next create manifest listing, example manifest.json:
    [
        {
            "path": "segments/terrier-jpg-one",
            "etag": "f7365c1419b4f349592c00bd0cfb9b9a",
            "size_bytes": 4000000
        },
        {
            "path": "segments/terrier-jpg-two",
            "etag": "ad81e97b10e870613aecb5ced52adbaa",
            "size_bytes": 2000000
        },
            "path": "segments/terrier-jpg-three",
            "etag": "00b046c9d74c3e8f93b320c5e5fdc2c3",
            "size_bytes": 1000
        {
        }
The final operation is to upload this content into a manifest object. To indicate that this is a manifest object, you need to specify the ?multipart-manifest=put query string.
$ curl –X PUT -i -H "X-Auth-Token: 12345" -T ./manifest.json
    https://storage.swiftdrive.com/v1/CF_xer7_343/images/terrier-jpg?multipart-manifest=put

*/
	
	global $x_auth_token, $x_storage_url;

	if (filesize($file) <= 4999999999) {
		die("File size of $file less than 5GB, doesn't need to be split before uploading." . br());
	}

	filesplit($file, 4900000000);

	// $curl = curl_init("$x_storage_url/$container/$folder/$file");   FINISH THIS TOMORROW, NEED TO UPLOAD SEGMENTS & MANIFEST

	// $curl_options = array(
	// 		CURLOPT_PUT => 1,
	// 		CURLOPT_INFILE => $file_to_upload,
	// 		CURLOPT_INFILESIZE => filesize($file),
	// 		CURLOPT_HTTPHEADER => array("X-Auth-Token: $x_auth_token"),
	// 		CURLOPT_SSL_VERIFYPEER => false,
	// 		CURLOPT_VERBOSE => 1
	// 	);

	// curl_setopt_array($curl, $curl_options);

	// curl_exec($curl);

	// curl_close($curl);


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

function filesplit($filename, $piecesize) {
/*	
This function is to overcome the Openstack 5GB object limit.
It splits $filename into $piecesize sized pieces.
$filename = File to split, if its not in the same folder, full path is required.
$piecesize = File size in Mb per piece/split.
*/
	$buffer = 1024;
	$piece = 1048576*$piecesize;	//converts piece size to bytes
	$current = 0;
	$splitnum = 1;

	if(!$handle = fopen($filename, "rb")) {			//tries to open file and return a handle
		die("Unable to open $filename for read!" . br());
	}

	$base_filename = basename($filename);

	$piece_name = $base_filename . '.' . str_pad($splitnum, 3, "0", STR_PAD_LEFT); //sets piece file name

	if(!$piece_being_written = fopen($piece_name,"w")) {
		die("Unable to open $piece_name for write. Make sure target folder is writeable.".br());
	}
	echo "Splitting $base_filename into $piecesize Mb files ".br()."(last piece may be smaller in size)".br();
	echo "Writing $piece_name...".br();

	while (!feof($handle) and $splitnum < 999) {	//while not at the end of file that's being split
		if($current < $piece) {						//if current byte count is smaller than the set piece size
			if($content = fread($handle, $buffer)) {//buffer sized content is read from file
				if(fwrite($piece_being_written, $content)) {//buffer is written to piece
					$current += $buffer;			//current byte count is incremented by 1024 (buffer's size)
				} else {
					die("Can't write to target folder. Target folder may not have write permission!".br());
				}
			}
		} else {			//else statment is triggered when piece size is reached, starts new piece
			fclose($piece_being_written);
			$current = 0;
			$splitnum++;
			$piece_name = $base_filename . '.' . str_pad($splitnum, 3, "0", STR_PAD_LEFT);
			echo "Writing $piece_name...".br();
			$piece_being_written = fopen($piece_name,"w");
		}
	}
	fclose($piece_being_written);
	fclose($handle);
	echo "Done! ".br();	
}

?>