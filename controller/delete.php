<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/init.php";

$request_handler = new controller\request_handler;
$request_handler->setrequest($post_sanitizer);
$api_deleter = new controller\api_deleter($request_handler);

?>