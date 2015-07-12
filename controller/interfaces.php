<?php

namespace controller;

interface auth_checker {

	static public function authenticated();
}


interface authenticator {

	public function __construct(sanitizer $sanitizer);

	public function authenticate();
}

interface logout {

	static public function logout();
}

interface sanitizer {

	public function sanitize();
}

interface request {

	public function setrequest(sanitizer $sanitizer);

	public function getrequest();
}

interface uploader {

	public function __construct(request $request);

	public function upload();

	public function segment_upload();	
}

interface downloader {

	public function __construct(request $request);

	public function download();	
}

interface deleter {

	public function __construct(request $request);

	public function delete();	
}

?>