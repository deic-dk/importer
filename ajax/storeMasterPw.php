<?php

OCP\JSON::checkAppEnabled('downloader');
OCP\JSON::checkLoggedIn();

$master_pw = $_POST['master_pw'];

OC_Log::write('downloader',"Storing master password", OC_Log::WARN);

$ret = array();

if(!OC_downloader::storeMasterPw($master_pw)){
	OC_Log::write('downloader',"Returning error.", OC_Log::WARN);
	$ret['error'] = "ERROR: could not store master password.";
}

OCP\JSON::encodedPrint($ret);
