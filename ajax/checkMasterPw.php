<?php

OCP\JSON::checkAppEnabled('ocdownloader');
OCP\JSON::checkLoggedIn();

$ret = array();

OC_Log::write('ocdownloader',"Trying to decrypt master password", OC_Log::WARN);

$pw = OC_ocDownloader::getMasterPw();

$ret['pw'] = 0;

if($pw!==null && $pw!==false){
	OC_Log::write('ocdownloader',"OK - decrypted master password", OC_Log::WARN);
	$ret['pw'] = 1;
}

OCP\JSON::encodedPrint($ret);
