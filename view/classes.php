<?php

namespace view;

require_once $_SERVER['DOCUMENT_ROOT'] . "/view/interfaces.php";

class fs_renderer implements renderer
{
	public static function render() {

		if (strlen(@$_SESSION['pwd'])) {
			$rendered_model = "<img src='/assets/images/up.png' class='up'>";
		} else {
			$rendered_model = '';
		}

		$rendered_model .= '<pre>';

		//initialize present working directory if unset
		if (!isset($_SESSION['pwd'])) {
			$_SESSION['pwd'] = '';
		}		
		// if pwd not set, get and render root containers
		if ($_SESSION['pwd'] == '') {
			
			//store containers in array $containers & render
			$curl = curl_init($_SESSION['x_storage_url']);

			$curl_options = array(
					CURLOPT_HTTPHEADER => array("X-Auth-Token: ". $_SESSION['x_auth_token']),
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_RETURNTRANSFER => true
				);

			curl_setopt_array($curl, $curl_options);

			$containers = curl_exec($curl);

			//reset present working directory to root if path gets messed up
			if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 404 ) {
				$_SESSION['pwd'] = '';
			}

			if (curl_error($curl)) {
				$_SESSION['pwd'] = '';
				$rendered_model = curl_error($curl);
				return $rendered_model . '</pre>';
			}

			curl_close($curl);
		
			$containers = explode("\n", $containers);
			foreach ($containers as $container) {
				if ($container != '') {
					$rendered_model.= "<div class='containers' id='$container' data-selected='0'><img src='/assets/images/folder.png' style='display:inline'/><div class='name'>$container</div></div>";	
				}	
			}
		//grab contents in json & render pwd folders & files
		} else {
			$path = explode('/', $_SESSION['pwd']);

			//get container's recursive contents in json
			$url = $_SESSION['x_storage_url'] . '/' . $path[0] . '?format=json';
						
			$curl = curl_init($url);

			$curl_options = array(
					CURLOPT_HTTPHEADER => array("X-Auth-Token: ". $_SESSION['x_auth_token']),
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_RETURNTRANSFER => true
				);

			curl_setopt_array($curl, $curl_options);

			$contents = curl_exec($curl);
			
			//reset present working directory to root if path gets messed up
			if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 404 ) {
				$_SESSION['pwd'] = '';
			}

			if (curl_error($curl)) {
				$rendered_model = curl_error($curl);
				return $rendered_model;
			}

			curl_close($curl);

			$contents = json_decode($contents);

			//if present working directory is a container
			if (sizeof($path) == 1) {

				foreach ($contents as $key => $object) {
					//render current level folders
					$name = $object->name;
					$type = $object->content_type;

					if ($type == 'application/directory') {
						@$rendered_model.= "<div class='folders' id='$name' data-selected='0'><img src='/assets/images/folder.png' style='display:inline'/><div class='name'>$name</div></div>";
					} else if ($type != 'application/directory' && strpos($name, '/') === FALSE) {
					//render current level files

						$name = $object->name;
						$size = $object->bytes;

						if ($size >= 1024 AND $size < 1048576) {		
							$size = round(($size / 1024), 2) . " KB";	//converting bytes to kilobytes for output
						}

						if ($size >= 1048576) {
							$size = round(($size / 1048576), 2) . " MB"; //converting bytes to megabytes for output
						}

						$name = $name . "  -  $size";

						@$rendered_model.= "<div class='files' id='$name' data-selected='0'><img src='/assets/images/file.png' style='display:inline'/><div class='name'>$name</div></div>";
					}
				}
			}
			
			//if present working directory is a container/folder..., grab contents in json & render only current level folders & files
			if (sizeof($path) > 1) {

				//turn pwd into a string containing 'folder/folder/folder/'
				array_shift($path);
				$pwd = implode('/', $path);
				$pwd .= '/';

				//iterate through containers content (array of objects) to render pwd's folders and files
				foreach ($contents as $key => $object) {
					$type = $object->content_type;
					$name = $object->name;

					//get object's parent folders into a string 'folder/folder/folder' to compare to $pwd
					$offset = strrpos($name, '/');
					$objpath = substr($name, 0, $offset + 1);

					$name = basename($name);

					//if we find an object with the same path as pwd
					if ($pwd == $objpath) {
						//render pwd folders
						if ($type == 'application/directory') {
							@$rendered_model.= "<div class='folders' id='$name' data-selected='0'><img src='/assets/images/folder.png' style='display:inline'/><div class='name'>$name</div></div>";
						//render pwd files
						} else if ($type != 'application/directory' && strpos($name, '/') === FALSE) {

							$size = $object->bytes;

							if ($size >= 1024 AND $size < 1048576) {		
								$size = round(($size / 1024), 2) . " KB";	//converting bytes to kilobytes for output
							}

							if ($size >= 1048576) {
								$size = round(($size / 1048576), 2) . " MB"; //converting bytes to megabytes for output
							}

							$name = $name . "  -  $size";

							@$rendered_model.= "<div class='files' id='$name' data-selected='0'><img src='/assets/images/file.png' style='display:inline'/><div class='name'>$name</div></div>";
						}
					}	
				}
			}

		}

		return $rendered_model . '</pre>';
	}
}

?>