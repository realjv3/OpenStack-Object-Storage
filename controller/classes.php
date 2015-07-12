<?php

namespace controller;

require_once 'interfaces.php';



class api_auth_checker implements auth_checker
{
	
	public static function authenticated() {
		
		if (@!$_SESSION['x_auth_token']) {

			return false;

		} else {
			
			return true;
		}
	}
}



class api_authenticator implements authenticator
{

	private $username;
	private $password;
	private $sanitizer;

	public function __construct(sanitizer $sanitizer) {

		$this->sanitizer = $sanitizer;

		$postdata = $sanitizer->sanitize();

		$this->username = $postdata['username'];
		$this->password = $postdata['password'];
	}

	public function authenticate() {
	/*
	Use cUrl to get API token and a URL that contains to full path to object storage account. 
	cUrl options set: public url to connect to, custom http headers to send (username and password), 
	set cUrl to accept any SSL server (SSL probz), 
	set cUrl to output http header, set cUrl to output to string instead of stdout;
	*/
		$curl = curl_init("https://dal05.objectstorage.softlayer.net/auth/v1.0/");

		$curl_options = array (
			CURLOPT_HTTPHEADER => array("X-Auth-User: $this->username", "X-Auth-Key: $this->password"),
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
		$_SESSION['x_auth_token'] = substr($curl_result[5], 0, -18);
		$_SESSION['x_storage_url'] = substr($curl_result[7], 0, -15);
	}
}



class api_logout implements logout
{
	static public function logout() {

		session_unset();
		session_destroy();
	}
}



class post_sanitizer implements sanitizer
{
	public function sanitize() {

		foreach ($_POST as $key => $value) {

			$postdata[$key] = htmlspecialchars($value);
		}

		return $postdata;
	}
}



class request_handler implements request
{
	private $type;
	private $container;
	private $folder;
	private $uploadfiles;
	private $downloadfiles;
	private $deletefiles;

	private $big_files;
	private $little_files;

	public function setrequest(sanitizer $sanitizer) {

		$postdata = $sanitizer->sanitize();

		if ($postdata['type'] != '') $this->type = $postdata['type'];
		if (@$postdata['container'] != '') {
			$this->container = $postdata['container'];
		} else {
			$this->container = '';
		}
		if (@$postdata['folder'] != '') {
			$this->folder = $postdata['folder'];
		} else {
			$this->folder = '';
		};
		
		if ($this->type == 'browseup') {
			$lastslash = strrpos($_SESSION['pwd'], '/');
			$_SESSION['pwd'] = substr($_SESSION['pwd'], 0, $lastslash);
		}

		if ($this->type == 'browsedown') {
			//if container is set, set $_SESSION['pwd']
			if ($this->container != '') $_SESSION['pwd'] = $this->container;

			//if folder is set, append folder to $_SESSION['pwd'], which should already have the container in the path
			if ($this->folder != '') $_SESSION['pwd'] .= '/' . $this->folder;
		}

		if ($this->type == 'download') {
			$this->downloadfiles = $postdata['downloadfiles'];
			$this->downloadfiles = explode(',', $this->downloadfiles);
		}

		if ($this->type == 'delete') {
			$this->deletefiles = explode(',', $postdata['deletefiles']);
		}

		if ($this->type == 'upload') {
			$this->uploadfiles = $postdata['uploadfiles'];
			
			//sort files to be uploaded by size greater or less than 5GB
			$this->uploadfiles = explode(',', $this->uploadfiles);
			foreach ($this->uploadfiles as $file) {

				$filesize = shell_exec('for %I in (' . str_pad($file, strlen($file) + 2, "\"", STR_PAD_BOTH) . ') do @echo %~zI'); //using a shell command to get bytes b/c filesize() doesn't work > 2GB
				$filesize = substr($filesize, 0, -1);	//removing line break from end of string

				if ($filesize < 5000000000) {				//if filesize less than 5 GB 
					$this->little_files[] = trim($file); 
			 	} else { 
	 				$this->big_files[] = trim($file); 
				} 
			}
		}	
	}

	public function getrequest() {

		return array(
			'type' => $this->type, 
			'container' => $this->container, 
			'folder' => $this->folder, 
			'big_files' => $this->big_files, 
			'little_files' => $this->little_files,
			'uploadfiles' => $this->uploadfiles,
			'downloadfiles' => $this->downloadfiles,
			'deletefiles' => $this->deletefiles
		);
	}
}



class api_uploader implements uploader
{
	private $x_auth_token;
	private $x_storage_url;

	private $req;

	private $container;
	private $folder;
	private $big_files;
	private $little_files;

	public function __construct(request $request) {
		
		$this->x_auth_token = $_SESSION['x_auth_token'];
		$this->x_storage_url = $_SESSION['x_storage_url'];

		$this->req = $request->getrequest();
		
		$this->container = $this->req['container'];
		$this->folder = $this->req['folder'];
		$this->big_files = $this->req['big_files'];
		$this->little_files = $this->req['little_files'];

		if (count($this->little_files) != 0) $this->upload();
		if (count($this->big_files) != 0) $this->segment_upload();
	}

	/**
	* Upload a file to object storage:
	* Send an http PUT request $x_storage_url/$container/$folder/$file. 
	* fopen file that the transfer should be read from when uploading and give reference to cUrl. Give anticipated uploaded bytes to cUrl.
	* Set cUrl to accept any SSL server (SSL probz). Send the custom http header "X-Auth-Token: $x_auth_token" along with the PUT request.
	*/
	public function upload() {

		if ($this->folder == '') {
			$folder = $this->folder;
		} else {
			$folder = "$this->folder/";
		}
		
		foreach ($this->little_files as $file) {

			if (!is_readable($file)) {
				@$output .="$file is not readable.\n";
			} else {			
				$file_handle = fopen($file, 'r');
			}
			$filesize = shell_exec('for %I in (' . str_pad($file, strlen($file) + 2, "\"", STR_PAD_BOTH) . ') do @echo %~zI'); //using a shell command to get bytes b/c filesize() doesn't work > 2GB
			$filesize = substr($filesize, 0, -1);	//removing line break from end of string

			$file = basename($file);

			$curl = curl_init(str_replace(" ", "%20", "$this->x_storage_url/$this->container/" . $folder . $file));

			$curl_options = array(
					CURLOPT_PUT => 1,
					CURLOPT_INFILE => $file_handle,
					CURLOPT_INFILESIZE => $filesize,
					CURLOPT_HTTPHEADER => array("X-Auth-Token: $this->x_auth_token", "Content-Length: $filesize"),
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_VERBOSE =>true
				);

			curl_setopt_array($curl, $curl_options);

			curl_exec($curl);

			$results = curl_getinfo($curl);
			if ($results['http_code'] == 201) {
				if ($filesize >= 1024 AND $filesize < 1048576) {		
					$filesize = round(($filesize / 1024), 2) . " KB";	//converting bytes to kilobytes for output
				}

				if ($filesize >= 1048576) {
					$filesize = round(($filesize / 1048576), 2) . " MB"; //converting bytes to megabytes for output
				}
				@$output .= "Uploaded $file - $filesize.<br/>";
			} else {
				$output .= "There was a problem uploading $file.<br/>" . curl_error($curl);
			}

			curl_close($curl);
			fclose($file_handle);
		}
		print_r($output);
	}

	/**
	* Pass in destination in Object Storage that file should be uploaded to. 
	* Filespit() function must be ran on $file first before calling this function, because this function will look for segment names.
	* Creation of a static large object is done in several steps. 
	* First we divide the content into pieces using filesplit function and upload each piece into a segment object. 
	* Then we create a manifest object. We will place the segment objects into the "Segments" container and the manifest object into the "Images" container.
	*/
	function segment_upload() {
		
		foreach ($this->big_files as $file) {

			$this->filesplit($file, 1000);

			$file = basename($file);

			$manifest_contents = "["; //starting manifest contents json, before the loop

			$ext = 1;										//setting first segment file extension	
			$ext = str_pad($ext, 3, "0", STR_PAD_LEFT);		//padding extension to the left with zeros
			$segment = $file . "." . $ext;			//adding extension to the file name, to be passed to fopen
			$segment = trim($segment);

			while ($file_handle = @fopen($segment, 'r')) { /*while there are segment files in this directory*/

				$filesize = shell_exec('for %I in (' . $segment . ') do @echo %~zI'); //using a shell command to get bytes b/c filesize() doesn't work > 2GB
				$filesize = substr($filesize, 0, -1);	//removing line break from end of string

				$curloutput = fopen('curloutput.txt', 'w+');	//curloutput.txt will contain http header responses we need for manifest creation

				$curl = curl_init("$this->x_storage_url/Segments/$segment");

				$curl_options = array(						//uploading the file with http put, response goes into file
						CURLOPT_PUT => 1,
						CURLOPT_INFILE => $file_handle,
						CURLOPT_INFILESIZE => "$filesize",
						CURLOPT_HEADER => 1,
						CURLOPT_HTTPHEADER => array("X-Auth-Token: $this->x_auth_token", "Content-Length: $filesize"),
						CURLOPT_SSL_VERIFYPEER => false,
						CURLOPT_FILE => $curloutput,
						CURLOPT_VERBOSE => true
					);

				curl_setopt_array($curl, $curl_options);

				curl_exec($curl);

				$results = curl_getinfo($curl);
				if ($results['http_code'] == 201) {
					if ($filesize >= 1024 AND $filesize < 1048576) {		
						$filesize = round(($filesize / 1024), 2) . " KB";	//converting bytes to kilobytes for output
					}

					if ($filesize >= 1048576) {
						$filesize = round(($filesize / 1048576), 2) . " MB"; //converting bytes to megabytes for output
					}
					@$output .= "Uploaded $segment - $filesize.<br/>";
				} else {
					$this->cleanup();
					return("There was a problem uploading $file." . curl_error($curl));
					fclose($curloutput);
					fclose($file_handle);
					curl_close($curl);
				}


				$size = curl_getinfo($curl, CURLINFO_SIZE_UPLOAD);	//getting uploaded bytes for use in manifest file	
				
				fclose($file_handle);
				fclose($curloutput);
				curl_close($curl);
				print_r($output);

				//This section the json manifest file will be created for the uploaded segments
				$httpheader = file_get_contents("curloutput.txt");
				$httpheader = explode("\r\n", $httpheader);	//exploding curl output into an array and getting the etag
				$etag = substr($httpheader[5], 6);
				if (substr($etag, 4) == ",") { //if the etag in the returned is a date, return
					cleanup();
					return("$segment upload failed, invalid etag returned.HTTP code $http_code\n");
				}
				$json_enc = array(	'path' => "Segments/$segment",   
									'etag' => "$etag",
									'size_bytes'=> "$size");
				$json_enc = json_encode($json_enc, JSON_UNESCAPED_SLASHES);		//encoding $json_enc into json		
				$manifest_contents .= $json_enc . ",";							//then appending to manifest contents

				$ext++;									//setup of next filename to upload
				$ext = str_pad($ext, 3, "0", STR_PAD_LEFT);
				$segment = $file . "." . $ext;
			}
		}

		$manifest_contents = substr($manifest_contents, 0, -1);	//takes the last comma off of the manifest contents
		$manifest_contents .= "]";								//appends "]" onto the end of manifest for json object syntax
		file_put_contents("$file.json", $manifest_contents);
		$manifest = fopen("$file.json", "r");

		//The final operation is to upload this content into a manifest object. To indicate that this is a manifest object, you need to specify the ?multipart-manifest=put query string.
		
		if ($this->folder == '') {
			$folder = $this->folder;
		} else {
			$folder = "$this->folder/";
		}

		$curl = curl_init("$this->x_storage_url/$this->container/" . $folder . $file . "?multipart-manifest=put");

		$curl_options = array(
				CURLOPT_PUT => 1,
				CURLOPT_INFILE => $manifest,
				CURLOPT_INFILESIZE => filesize("$file.json"),
				CURLOPT_HTTPHEADER => array("X-Auth-Token: $this->x_auth_token"),
				CURLOPT_SSL_VERIFYPEER => false,
			);

		curl_setopt_array($curl, $curl_options);

		curl_exec($curl);

		if ($error = curl_error($curl)) {
			echo "$error\n";
		} else {
			echo "Uploaded $file.json ok.\n";
		}
		curl_close($curl);
		$this->cleanup();
	}


	/**	
	* This function is to overcome the Openstack 5GB object limit.
	* It splits $filename into $piecesize sized pieces.
	* $filename = File to split, if its not in the same folder, full path is required.
	* $piecesize = File size in Mb per piece/split.
	*/
	private function filesplit($filename, $piecesize) {
		$buffer = 1024;
		$piece = 1048576*$piecesize;	//converts piece size to bytes
		$current = 0;
		$splitnum = 1;

		if(!$handle = fopen($filename, "r")) {			//tries to open file and return a handle
			return("Unable to open $filename for read!<br/>");
		}

		$base_filename = basename($filename);

		$piece_name = $base_filename . '.' . str_pad($splitnum, 3, "0", STR_PAD_LEFT); //sets piece file name

		if(!$piece_being_written = fopen($piece_name,"w")) {
			return("Unable to open $piece_name for write. Make sure target folder is writeable.<br/>");
		}
		echo "Splitting $base_filename into $piecesize Mb files <br/>(last piece may be smaller in size)<br/>";
		echo "Writing $piece_name...<br/>";

		while (!feof($handle) and $splitnum < 999) {	//while not at the end of file that's being split
			if($current < $piece) {						//if current byte count is smaller than the set piece size
				if($content = fread($handle, $buffer)) {//buffer sized content is read from file
					if(fwrite($piece_being_written, $content)) {//buffer is written to piece
						$current += $buffer;			//current byte count is incremented by 1024 (buffer's size)
					} else {
						return("Can't write to target folder. Target folder may not have write permission!<br/>");
					}
				}
			} else {			//else statment is triggered when piece size is reached, starts new piece
				fclose($piece_being_written);
				$current = 0;
				$splitnum++;
				$piece_name = $base_filename . '.' . str_pad($splitnum, 3, "0", STR_PAD_LEFT);
				echo "Writing $piece_name...<br/>";
				$piece_being_written = fopen($piece_name,"w");
			}
		}
		fclose($piece_being_written);
		fclose($handle);
		echo "Done!<br/>";	
	}

	/**
	* This function is to clean up segment & json files that are created by filesplit + segment upload functions
	*/	
	private function cleanup() {

		$dir = opendir(".");
		while ($file = readdir($dir)) {
			if (fnmatch("*.*.*", "$file") || fnmatch("*.json", "$file") || fnmatch("curloutput.txt", "$file")) @unlink("$file");
		}
		closedir();
	}
}



class api_downloader implements downloader
{
    private $x_auth_token;
	private $x_storage_url;

	private $req;
	private $files;

	public function __construct(request $request) {
		
		$this->x_auth_token = $_SESSION['x_auth_token'];
		$this->x_storage_url = $_SESSION['x_storage_url'];

		$this->req = $request->getrequest();
		$this->files = $this->req['downloadfiles'];

		if ($this->files != '') $this->download();
	}

    /**
	* Download a file from object storage:
	* Send an http GET request (which cUrl defaults to) to $x_storage_url/$container/$folder/$file and have cUrl pipe response into a file.
	* Set cUrl to accept any SSL server (SSL probz). Send the custom http header "X-Auth-Token: $x_auth_token" along with the GET request.
	*/
	public function download() {

		foreach ($this->files as $file) {
		
			$basename = basename($file);

			$curl = curl_init(str_replace(' ', '%20', "$this->x_storage_url/". $_SESSION['pwd'] . "/$file"));

			$curl_options = array(
					CURLOPT_HTTPHEADER => array("X-Auth-Token: $this->x_auth_token"),
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_FILE => fopen("$basename", "w"),
					CURLOPT_VERBOSE => 1
				);

			curl_setopt_array($curl, $curl_options);

			curl_exec($curl);

			curl_close($curl);

			if (is_readable($basename)) {
				echo "$file has been downloaded.\n";
			} else {
				echo "There was a problem with downloading $file.\n";
			}	
		}
	}
}



class api_deleter implements deleter
{
    private $x_auth_token;
	private $x_storage_url;

	private $req;
	private $files;

	public function __construct(request $request) {
		
		$this->x_auth_token = $_SESSION['x_auth_token'];
		$this->x_storage_url = $_SESSION['x_storage_url'];

		$this->req = $request->getrequest();
		$this->files = $this->req['deletefiles'];
		if ($this->files != '') $this->delete();
	}

	/**
	* Delete a file from object storage. Pass in $file as container/folder/file
	* Send a http DELETE request to $x_storage_url/$container/$folder/$file.
	* Send the custom http header "X-Auth-Token: $x_auth_token" along with the DELETE request.
	*/
	public function delete() {

		foreach ($this->files as $file) {

			$curl = curl_init(str_replace(" ", "%20", "$this->x_storage_url/". $_SESSION['pwd'] . "/$file"));

			$curl_options = array(
					CURLOPT_CUSTOMREQUEST => "DELETE",
					CURLOPT_HTTPHEADER => array("X-Auth-Token: $this->x_auth_token"),
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
	}
}

?>