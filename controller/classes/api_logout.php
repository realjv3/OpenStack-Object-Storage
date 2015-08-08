<?php

namespace controller;

require_once '../interfaces/interfaces.php';


class api_logout implements logout
{
	static public function logout() {

		session_unset();
		session_destroy();
	}
}

?> 
