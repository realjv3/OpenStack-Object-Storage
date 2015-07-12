<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/includes/init.php";

controller\api_logout::logout();

header("location: /index.php");

?>