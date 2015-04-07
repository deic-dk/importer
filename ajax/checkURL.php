<?php

OCP\JSON::checkLoggedIn();

$url = $_GET['url'];

echo json_encode(array(
	'status' => 'success',
	'message'=> shell_exec("curl -I ".$url)
));

