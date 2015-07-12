<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/init.php";

$request_handler = new controller\request_handler;
$request_handler->setrequest($post_sanitizer);

$api_uploader = new controller\api_uploader($request_handler);

?>