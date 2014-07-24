<?php

OCP\JSON::checkAppEnabled('importer');
OCP\JSON::checkLoggedIn();

$ret = array();

OC_Log::write('importer',"Trying to decrypt master password", OC_Log::WARN);

$pw = OC_importer::getMasterPw();

$ret['pw'] = 0;

if($pw!==null && $pw!==false){
	OC_Log::write('importer',"OK - decrypted master password", OC_Log::WARN);
	$ret['pw'] = 1;
}

OCP\JSON::encodedPrint($ret);
