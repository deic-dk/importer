<?php

/**
* ownCloud importer app
*
* @author Xavier Beurois
* @copyright 2012 Xavier Beurois www.djazz-lab.net
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

OCP\JSON::checkAppEnabled('importer');
OCP\JSON::checkLoggedIn();

$folderurl = $_POST['url'];
$provider = array_key_exists ('provider' , $_POST)?$_POST['provider']:"";

OC_Log::write('importer',"Provider: ".$provider, OC_Log::WARN);

$user_info = NULL;

$k = array();

$providers = OC_importer::getUserProvidersList(1, 1);

if(empty($provider)){
  foreach($providers as $p){
    if(preg_match('/^'.$p['pr_name'].'s*:\/\//i', $folderurl)){
      $provider = 'pr_'.$p['pr_id'];
      break;
    }
  }
}

OC_Log::write('importer',"Provider: ".$provider, OC_Log::WARN);

if(!empty($provider)){
	foreach($providers as $p){
		if($provider==='pr_'.$p['pr_id']){
			$myprovider = $p['pr_name'];
		}
	}
	if(preg_match('/^pr_([^_^&]+)$/', $provider, $m)){
		$user_info = OC_importer::getUserProviderInfo($m[1]);
	}
	else{
		OC_Log::write('importer', "Bad format of provider ID: ".$provider, OC_Log::ERROR);
		$k['error'] = "Protocol not supported.";
	}
}

$url = parse_url($folderurl);
$port = 0;
if(isset($url['port'])){
	$port = $url['port'];
}
if(isset($url['user'])){
	$user_info['us_username'] = $url['user'];
}
if(isset($url['pass'])){
	$user_info['us_password'] = $url['pass'];
}

//$folderurl = trim(escapeshellarg($folderurl . (substr($folderurl,-1)==='/'?"":"/")));
$folderurl = trim($folderurl);
$folderurl = $folderurl . (substr($folderurl,-1)==='/'?"":"/");
$out = array();

require_once('importer/lib/importer'.$myprovider.'.class.php');
$myprovider = 'OC_importer'.$myprovider;
try{
	$out = $myprovider::lsDir($folderurl, $user_info);
}
catch(Exception $e){
	$k['error'] = $e->getMessage();
}

$i = 0;
foreach ($out as &$value) {
	// Drop directories
	for($j=1; $j<$i; ++$j){
		if(!array_key_exists('url'.$j, $k)){
			continue;
		}
		$pattern = '|^'.$k['url'.$j].'/|';
		if(preg_match($pattern, $value)){
			unset($k['url'.$j]);
			break;
		}
  }
  if(substr($value, -1)==='/'){
  	continue;
  }
  //OC_Log::write('importer',$i.' --> '.$k['url'.$i], OC_Log::WARN);
  $i = $i+1;
  $k['url'.$i] = $value;
}

OCP\JSON::encodedPrint($k);

