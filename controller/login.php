<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/init.php";

if (!$_POST['username'] || !$_POST['password']) {

	die("Please input username and password");
}

//log the user in
$api_authenticator = new controller\api_authenticator($post_sanitizer);
$api_authenticator->authenticate();

header("location: ../index.php");

?>