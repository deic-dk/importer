<?php

OCP\JSON::checkAppEnabled('ocdownloader');
OCP\JSON::checkLoggedIn();

$enc_pw = $_POST['enc_pw'];

$ret = array();

OC_Log::write('ocdownloader',"Trying to decrypt ".$enc_pw, OC_Log::WARN);

$pw = OC_ocDownloader::decryptPw($enc_pw);

if($pw!==null && $pw!==false){
	$ret['pw'] = $pw;
}
else{
	$ret['error'] = "ERROR: could not decrypt. ".$pw;
}


OCP\JSON::encodedPrint($ret);
