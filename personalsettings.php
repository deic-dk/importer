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
OCP\User::checkLoggedIn();

OCP\Util::addscript('importer', 'personalsettings');
OCP\Util::addscript('importer', 'browse');

OCP\Util::addStyle('chooser', 'jqueryFileTree');
OCP\Util::addscript('chooser', 'jquery.easing.1.3');
OCP\Util::addscript('chooser', 'jqueryFileTree');

$errors = Array();

if(isset($_GET['importer']) && $_GET['importer'] == 1){
	foreach($_GET as $key => $value){
		$value = trim($value);
		if(strcmp(substr($key, 0, 15), 'importer_pr_un_') == 0){
			$pr_id = substr($key, strrpos($key, '_')+1);
			if(strlen($value) != 0){
				if(is_numeric($pr_id)){
					$pw = trim($_GET['importer_pr_pw_' . $pr_id]);
					$enc = trim($_GET['importer_pr_enc_' . $pr_id]);
					if($enc=="0" && $pw!=""){
						OC_Log::write('importer',"Updating user info ".$pr_id.": ".$pw.", ".$enc,OC_Log::WARN);
						if(OC_importer::updateUserInfo($pr_id, $value, $pw)===false){
							$errors[$pr_id] = "ERROR: password NOT updated for ".$value.". " . mysql_error();
						}
					}
				}
			}
			else{
				if(is_numeric($pr_id)){
					OC_importer::deleteUserInfo($pr_id);
				}
			}
		}
		if(strcmp(substr($key, 0, 24), 'importer_download_folder') == 0 && trim($value) != ""){
			OC_importer::updateDownloadFolder($value);
		}
	}
}

OC_Log::write('importer',"Cookie has value ".$_COOKIE['importerPw'], OC_Log::WARN);

$ret = openssl_decrypt('dum', 'aes-128-cbc', 'test');
OC_Log::write('importer',"Decryption test: ".$ret, OC_Log::WARN);

$tmpl = new OCP\Template('importer', 'personalsettings.tpl');
$tmpl->assign('errors', $errors);
$tmpl->assign('pr_list', OC_importer::getUserProvidersList(1));
$tmpl->assign('us_download_folder', OC_importer::getDownloadFolder(1));
return $tmpl->fetchPage();
