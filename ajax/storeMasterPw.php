<?php

OCP\JSON::checkAppEnabled('importer');
OCP\JSON::checkLoggedIn();

$master_pw = $_POST['master_pw'];

OC_Log::write('importer',"Storing master password", OC_Log::WARN);

$ret = array();

if(!OC_importer::storeMasterPw($master_pw)){
	OC_Log::write('importer',"Returning error.", OC_Log::WARN);
	$ret['error'] = "ERROR: could not store master password.";
}

OCP\JSON::encodedPrint($ret);
