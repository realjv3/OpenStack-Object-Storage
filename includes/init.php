<?php

session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/controller/classes.php';	
require_once $_SERVER['DOCUMENT_ROOT'] . '/view/classes.php';	

//Login form and model view that is passed to index.php

if (controller\api_auth_checker::authenticated()) {
	
	$login_form = '	<form id="login" action="/controller/logout.php">
						<input type="submit" value="Log out"/>
					</form>';
	$renderedmodel = view\fs_renderer::render();

} else {

	$login_form = '	<form id="login" method="post" enctype="multipart/form-data" action="/controller/login.php"
						<label for="username">Username:</label>	<input type="text" name="username"/>
						<label for="password">Password:</label>	<input type="password" name="password"/>
						<input type="submit" value="login"/>
					</form>';	
	$renderedmodel = "Please login.";				
}

//Instance of sanitizer for controller stuff from index.php
$post_sanitizer = new controller\post_sanitizer;

?>