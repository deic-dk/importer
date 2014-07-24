<?php

OCP\JSON::checkAppEnabled('importer');
OCP\JSON::checkLoggedIn();

$file_name = $_POST['file_name'];
$list = $_POST['list'];


$dl_dir = OC_importer::getDownloadFolder();
$fs = OCP\Files::getStorage('files');

$full_name = $dl_dir."/".$file_name;

OC_Log::write('importer',"Saving list ".$full_name.", content: ".$list, OC_Log::WARN);

if(!$fs->file_put_contents($full_name, $list)){
	$ret['error'] = "Failed saving to ".$full_name;
}
else{
	$ret['msg'] = "Saved list to ".$full_name;
}

OCP\JSON::encodedPrint($ret);
