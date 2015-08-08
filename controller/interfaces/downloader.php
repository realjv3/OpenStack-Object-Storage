<?php

namespace controller;

interface downloader {

	public function __construct(request $request);

	public function download();	
}

?>