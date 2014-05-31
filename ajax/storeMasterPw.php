<?php

OCP\JSON::checkAppEnabled('ocdownloader');
OCP\JSON::checkLoggedIn();

$master_pw = $_POST['master_pw'];

OC_Log::write('ocDownloader',"Storing master password", OC_Log::WARN);

$ret = array();

if(!OC_ocDownloader::storeMasterPw($master_pw)){
	OC_Log::write('ocDownloader',"Returning error.", OC_Log::WARN);
	$ret['error'] = "ERROR: could not store master password.";
}

OCP\JSON::encodedPrint($ret);
