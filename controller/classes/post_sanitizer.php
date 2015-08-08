<?php

namespace controller;

require_once '../interfaces/interfaces.php';


class post_sanitizer implements sanitizer
{
	public function sanitize() {

		foreach ($_POST as $key => $value) {

			$postdata[$key] = htmlspecialchars($value);
		}

		return $postdata;
	}
}

?> 
