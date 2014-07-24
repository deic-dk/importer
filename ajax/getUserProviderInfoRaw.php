<?php

OCP\JSON::checkAppEnabled('importer');
OCP\JSON::checkLoggedIn();

$folderurl = $_POST['url'];
$provider = array_key_exists ('provider' , $_POST)?$_POST['provider']:"";

OC_Log::write('importer',"Provider: ".$provider, OC_Log::WARN);

$user_info = NULL;

$providers = OC_importer::getUserProvidersList(1, 1);

if(empty($provider)){
  foreach($providers as $p){
    if(preg_match('/^'.$p['pr_name'].'s*:\/\//i', $folderurl)){
      $provider = 'pr_'.$p['pr_id'];
      break;
    }
  }
}

if(!empty($provider)){
	foreach($providers as $p){
		if($provider==='pr_'.$p['pr_id']){
			$myprovider = $p['pr_name'];
			break;
		}
	}
}

if(!empty($myprovider) && preg_match('/^pr_([^_^&]+)$/', $myprovider, $m)){
	$user_info = OC_importer::getUserProviderInfoRaw($m[1]);
}
else{
	OC_Log::write('importer', "Bad format of provider ID: ".$provider, OC_Log::ERROR);
	$user_info['error'] = "Protocol not supported: ".$myprovider;
}

OC_Log::write('importer',"Returning: ".serialize($user_info), OC_Log::WARN);

OCP\JSON::encodedPrint($user_info);

