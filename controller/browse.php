<?php

session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/controller/classes.php';	
require_once $_SERVER['DOCUMENT_ROOT'] . '/view/classes.php';
	
$post_sanitizer = new controller\post_sanitizer;

$request_handler = new controller\request_handler;
$request_handler->setrequest($post_sanitizer);

$output = view\fs_renderer::render();
echo $output;

?>