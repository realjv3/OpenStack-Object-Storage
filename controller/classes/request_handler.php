<?php

namespace controller;

require_once '../interfaces/interfaces.php';


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

?> 
