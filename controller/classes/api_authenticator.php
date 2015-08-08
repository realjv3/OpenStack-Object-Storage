<?php

namespace controller;

require_once '../interfaces/interfaces.php';


class api_authenticator implements authenticator
{

	private $username;
	private $password;
	private $sanitizer;

	public function __construct(sanitizer $sanitizer) {

		$this->sanitizer = $sanitizer;

		$postdata = $sanitizer->sanitize();

		$this->username = $postdata['username'];
		$this->password = $postdata['password'];
	}

	public function authenticate() {
	/*
	Use cUrl to get API token and a URL that contains to full path to object storage account. 
	cUrl options set: public url to connect to, custom http headers to send (username and password), 
	set cUrl to accept any SSL server (SSL probz), 
	set cUrl to output http header, set cUrl to output to string instead of stdout;
	*/
		$curl = curl_init("https://dal05.objectstorage.softlayer.net/auth/v1.0/");

		$curl_options = array (
			CURLOPT_HTTPHEADER => array("X-Auth-User: $this->username", "X-Auth-Key: $this->password"),
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_HEADER => 1,
			CURLOPT_RETURNTRANSFER => 1
			);

		curl_setopt_array($curl, $curl_options);

		$curl_result = curl_exec($curl);

		curl_close($curl);

	/*
	Get the API token and url from the returned http header and store them in vars to use in future operations
	*/

		$curl_result = explode(" ", $curl_result);
		$_SESSION['x_auth_token'] = substr($curl_result[5], 0, -18);
		$_SESSION['x_storage_url'] = substr($curl_result[7], 0, -15);
	}
}


?> 
