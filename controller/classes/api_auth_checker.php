 
<?php

namespace controller;

require_once '../interfaces/interfaces.php';



class api_auth_checker implements auth_checker
{
	
	public static function authenticated() {
		
		if (@!$_SESSION['x_auth_token']) {

			return false;

		} else {
			
			return true;
		}
	}
}

?>