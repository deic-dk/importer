<?php

/**
* ownCloud downloader app
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

OCP\JSON::checkAppEnabled('downloader');
OCP\User::checkLoggedIn();

OCP\Util::addscript('downloader', 'personalsettings');

$errors = Array();

if(isset($_GET['downloader']) && $_GET['downloader'] == 1){
	foreach($_GET as $key => $value){
		$value = trim($value);
		if(strcmp(substr($key, 0, 19), 'downloader_pr_un_') == 0){
			$pr_id = substr($key, strrpos($key, '_')+1);
			if(strlen($value) != 0){
				if(is_numeric($pr_id)){
					$pw = trim($_GET['downloader_pr_pw_' . $pr_id]);
					$enc = trim($_GET['downloader_pr_enc_' . $pr_id]);
					if($enc=="0" && $pw!=""){
						OC_Log::write('downloader',"Updating user info ".$pr_id.": ".$pw.", ".$enc,OC_Log::WARN);
						if(OC_downloader::updateUserInfo($pr_id, $value, $pw)===false){
							$errors[$pr_id] = "ERROR: password NOT updated for ".$value.". " . mysql_error();
						}
					}
				}
			}
			else{
				if(is_numeric($pr_id)){
					OC_downloader::deleteUserInfo($pr_id);
				}
			}
		}
		if(strcmp(substr($key, 0, 28), 'downloader_download_folder') == 0 && trim($value) != ""){
			OC_downloader::updateDownloadFolder($value);
		}
	}
}

OC_Log::write('downloader',"Cookie has value ".$_COOKIE['downloaderPw'], OC_Log::WARN);

$ret = openssl_decrypt('dum', 'aes-128-cbc', 'test');
OC_Log::write('downloader',"Decryption test: ".$ret, OC_Log::WARN);

$tmpl = new OCP\Template('downloader', 'personalsettings.tpl');
$tmpl->assign('errors', $errors);
$tmpl->assign('pr_list', OC_downloader::getUserProvidersList(1));
$tmpl->assign('us_download_folder', OC_downloader::getDownloadFolder(1));
return $tmpl->fetchPage();
