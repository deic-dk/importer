<?php

OCP\JSON::checkAppEnabled('importer');
OCP\JSON::checkLoggedIn();

$folderurl = $_POST['url'];
$provider = array_key_exists('provider' , $_POST)?$_POST['provider']:"";
$url = parse_url($folderurl);
$hostname = $url['host'];

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
else{
	foreach($providers as $p){
		if($provider==='pr_'.$p['pr_id']){
			$myprovider = $p['pr_name'];
			break;
		}
	}
}

$m = "";
if(!empty($provider)){
	if(preg_match('/^pr_([^_^&]+)$/', $provider, $m)){
		$user_info = OC_importer::getUserProviderInfoRaw($m[1], $hostname);
	}
	else{
		OC_Log::write('importer', "Bad format of provider ID: ".$provider.":".serialize($providers), OC_Log::ERROR);
		$user_info['error'] = "Protocol not supported: ".$myprovider.":".$provider;
	}
}

OC_Log::write('importer',"Returning: ".serialize($user_info), OC_Log::WARN);

OCP\JSON::encodedPrint($user_info);

