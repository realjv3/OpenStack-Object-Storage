f<?php

namespace controller;

interface uploader {

	public function __construct(request $request);

	public function upload();

	public function segment_upload();	
}

?>