<?php

session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/controller/browse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controller/classes';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controller/delete.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controller/download.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controller/interfaces';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controller/login.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controller/logout.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/controller/upload.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/view/fs_renderer.php';
	
$post_sanitizer = new controller\post_sanitizer;

$request_handler = new controller\request_handler;
$request_handler->setrequest($post_sanitizer);

$output = view\fs_renderer::render();
echo $output;

?>