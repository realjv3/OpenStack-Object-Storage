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

	$curl_result = explode(" ", $curl_result);
	$x_auth_token = substr($curl_result[5], 0, -18);
	$x_storage_url = substr($curl_result[7], 0, -15);

	return array(
		"x_auth_token" => $x_auth_token, 
		"x_storage_url" => $x_storage_url
	);
}

function upload($container, $folder, $file_array) {
/*
Upload an array of files to object storage:
Send an http PUT request $x_storage_url/$container/$folder/$file. 
fopen file that the transfer should be read from when uploading and give reference to cUrl. Give anticipated uploaded bytes to cUrl.
Set cUrl to accept any SSL server (SSL probz). Send the custom http header "X-Auth-Token: $x_auth_token" along with the PUT request.
*/

	global $x_auth_token, $x_storage_url;

	$mh = curl_multi_init();	//creating cUrl multi handle;

	foreach ($file_array as $key => $fname) {
		$file_handle = fopen("$fname", 'r');
		${'f' . $key} = curl_init("$x_storage_url/$container/$folder/$fname"); //using variable varible so each cUrl handle is stored in a unique varible;
		$curl_options = array(
				CURLOPT_PUT => 1,
				CURLOPT_INFILE => $file_handle,
				CURLOPT_INFILESIZE => filesize("$fname"),
				CURLOPT_HTTPHEADER => array("X-Auth-Token: $x_auth_token"),
				CURLOPT_SSL_VERIFYPEER => false,
			);

		curl_setopt_array(${'f' . $key}, $curl_options);

		curl_multi_add_handle($mh, ${'f' . $key}); //adding cUrl handle to multi handle
	}

	$active = null;

	do  { 								//executing cUrl multi handle until no more active files;
		curl_multi_exec($mh, $active);
		echo "$active files are still uploading.\n";
		sleep(3);
	} while ($active > 0);

	foreach ($file_array as $key => $fname) { //this loop outputs result of each uploaded file
		$httpcode = curl_getinfo(${'f' . $key}, CURLINFO_HTTP_CODE)."\n";
		if ($httpcode == 201) {
			echo "$fname uploaded ok.\n";
		} else {
			echo "There was a problem with $fname upload - http code $httpcode";
		}
	}

	curl_multi_close($mh);
}

function big_file_upload($container, $folder, $segments_array) {
/*
For uploading files > 5GB in segements (made by filesplit()) with a json manifest.
Pass in destination in Object Storage that file should be uploaded to, along with array of segments to be uploaded.
Creation of a static large object is done in several steps. First we divide the content into pieces using filesplit function and upload each piece into a segment object. Then we create a manifest object. We will place the segment objects into the "Segments" container and the manifest object into the "Images" container.
*/
	
	global $x_auth_token, $x_storage_url;

	$manifest_contents = "["; //starting manifest contents json, before the loop

	$mh = curl_multi_init();	//creating cUrl multi handle;

	foreach ($segments_array as $key => $fname) {
		$file_handle = fopen("$fname", 'r');
		$filesize = shell_exec('for %I in (' . $fname . ') do @echo %~zI'); //using a shell command to get bytes b/c filesize() doesn't work > 2GB
		${'f' . $key} = curl_init("$x_storage_url/Segments/$fname"); //using variable varible so each cUrl handle is stored in a unique varible;
		$curloutput = fopen("curloutput$key.txt", 'w+');	//curloutput.txt will contain http header responses we need for manifest creation
		$curl_options = array(
				CURLOPT_PUT => 1,
				CURLOPT_INFILE => $file_handle,
				CURLOPT_INFILESIZE => $filesize,
				CURLOPT_HTTPHEADER => array("X-Auth-Token: $x_auth_token"),
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_HEADER => 1,
				CURLOPT_FILE => $curloutput
			);

		curl_setopt_array(${'f' . $key}, $curl_options);

		curl_multi_add_handle($mh, ${'f' . $key}); //adding cUrl handle to multi handle
	}
//executing cUrl multi handle until no more active files;	
	$active = null;

	do  {
		curl_multi_exec($mh, $active);
		echo "$active files are still uploading.\n";
		sleep(3);
	} while ($active > 0);

	fclose($curloutput);

//This section will output upload results and the json manifest file will be created for the uploaded segments
	foreach ($segments_array as $key => $fname) { 

			$httpcode = curl_getinfo(${'f' . $key}, CURLINFO_HTTP_CODE)."\n";	//getting http response for each upload from a file
			
			if ($httpcode == 201) {

				echo "$fname uploaded ok.\n";

				$size = curl_getinfo(${'f' . $key}, CURLINFO_SIZE_UPLOAD);	//getting uploaded bytes for use in manifest file

				curl_multi_remove_handle($mh, ${'f' . $key}); //removing curl handle from multi curl handle
				curl_close(${'f' . $key}); //closing the removed curl handle

				$httpheader = file_get_contents("curloutput$key.txt");
				$etag = substr($httpheader, stripos($httpheader, "etag: "), 38);
				$etag = substr($etag, 6);	//getting 'etag' out of that array, need it for manifest

				$json_enc = array(	'path' => "Segments/$segments_array[$key]",   
									'size_bytes'=> "$size",
									'etag' => "$etag"
								);
				$json_enc = json_encode($json_enc, JSON_UNESCAPED_SLASHES);		//encoding $json_enc into json array syntax		
				$manifest_contents .= $json_enc . ",";						//then appending to manifest contents

			} else {
				die("There was a problem with $fname upload - http code $httpcode");
			}
		}
	curl_multi_close($mh); //closing multihandle, done with uploads and results


	$manifest_contents = substr($manifest_contents, 0, -1); //takes the comma off of end of the manifest contents

	$manifest_contents .= ']';
	$manifest_file = substr("$segments_array[0]", 0, -4) . ".json";
	file_put_contents($manifest_file, $manifest_contents);
	$manifest = fopen("$manifest_file", "r");

//The final operation is to upload this content into a manifest object. To indicate that this is a manifest object, you need to specify the ?multipart-manifest=put query string.
	$curl = curl_init("$x_storage_url/$container/$folder/$manifest_file?multipart-manifest=put");

	$curl_options = array(
			CURLOPT_PUT => 1,
			CURLOPT_INFILE => $manifest,
			CURLOPT_INFILESIZE => filesize("$manifest_file"),
			CURLOPT_HTTPHEADER => array("X-Auth-Token: $x_auth_token"),
			CURLOPT_SSL_VERIFYPEER => false,
		);

	curl_setopt_array($curl, $curl_options);

	curl_exec($curl);
	if ($error = curl_error($curl)) {
		echo "$error\n";
	} else {
		echo "Uploaded $segments_array[0].json ok.\n";
	}
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

function delete($container, $file) {
/*
Delete a file from object storage. Pass $container and folder\$file
Send a http DELETE request to $x_storage_url/$container/$folder/$file.
Send the custom http header "X-Auth-Token: $x_auth_token" along with the DELETE request.
*/

	global $x_auth_token, $x_storage_url;

	$curl = curl_init("$x_storage_url/$container/$file");

	$curl_options = array(
			CURLOPT_CUSTOMREQUEST => "DELETE",
			CURLOPT_HTTPHEADER => array("X-Auth-Token: $x_auth_token"),
			CURLOPT_SSL_VERIFYPEER => false
		);

	curl_setopt_array($curl, $curl_options);

	curl_exec($curl);
	if ($error = curl_error($curl)) {
		echo "$error\n";
	} else {
		echo "Deleted $file.\n";
	}
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

	if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 204) {
		echo "Container is empty.\n";
	}

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

	if(!$handle = fopen($filename, "r")) {			//tries to open file and return a handle
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