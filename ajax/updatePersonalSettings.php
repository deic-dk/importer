<?php

OCP\JSON::checkAppEnabled('importer');
OCP\User::checkLoggedIn();

$errors = Array();

if(isset($_POST['importer']) && $_POST['importer'] == 1){
	foreach($_POST as $key => $value){
		$value = trim($value);
		if(strcmp(substr($key, 0, 15), 'importer_pr_un_') == 0){
			OC_Log::write('importer',"Updating ".$key."-->".$value, OC_Log::WARN);
			$pr_id = substr($key, strrpos($key, '_')+1);
			if(strlen($value) != 0){
				if(is_numeric($pr_id)){
					$pw = trim($_POST['importer_pr_pw_' . $pr_id]);
					$enc = trim($_POST['importer_pr_enc_' . $pr_id]);
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
		if(strcmp(substr($key, 0, 24), 'importer_download_folder') == 0){
			OC_importer::updateDownloadFolder($value);
		}
	}
}

OC_Log::write('importer',"Updated settings. ".serialize($errors), OC_Log::WARN);

OCP\JSON::encodedPrint($errors);
