<?php

namespace controller;

interface authenticator {

	public function __construct(sanitizer $sanitizer);

	public function authenticate();
}

?> 