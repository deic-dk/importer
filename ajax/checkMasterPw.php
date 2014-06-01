<?php

OCP\JSON::checkAppEnabled('downloader');
OCP\JSON::checkLoggedIn();

$ret = array();

OC_Log::write('downloader',"Trying to decrypt master password", OC_Log::WARN);

$pw = OC_downloader::getMasterPw();

$ret['pw'] = 0;

if($pw!==null && $pw!==false){
	OC_Log::write('downloader',"OK - decrypted master password", OC_Log::WARN);
	$ret['pw'] = 1;
}

OCP\JSON::encodedPrint($ret);
