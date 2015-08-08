<?php

namespace controller;

interface deleter {

	public function __construct(request $request);

	public function delete();	
}

?>