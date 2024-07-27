<?php

OCP\JSON::checkAppEnabled('importer');
OCP\User::checkLoggedIn();

$errors = Array();

if(isset($_POST['importer']) && $_POST['importer'] == 1){
	foreach($_POST as $key => $value){
		$value = trim($value);
		if(strcmp(substr($key, 0, 15), 'importer_pr_un_') == 0){
			$pr_id_host = substr($key, 15);
			$pr_id = substr($pr_id_host, 0, strpos($pr_id_host, '_'));
			$hostname = substr($pr_id_host, strpos($pr_id_host, '_')+1);
			// PHP replaces . with _ in parameter names. Luckily _ is not allowed in hostnames, so we can just replace back
			$hostname = str_replace('_', '.', $hostname);
			OC_Log::write('importer',"Updating ".$key.":".$pr_id_host.":".$hostname.":".$pr_id."-->".$value, OC_Log::WARN);
			if(strlen($value) != 0){
				if(is_numeric($pr_id)){
					$pw = trim($_POST['importer_pr_pw_' . $pr_id_host]);
					$enc = trim($_POST['importer_pr_enc_' . $pr_id_host]);
					$real_pr_id = trim($_POST['importer_pr_id_' . $pr_id_host]);
					$real_hostname = trim($_POST['importer_pr_hn_' . $pr_id_host]);
					$real_username = trim($_POST['importer_pr_un_' . $pr_id_host]);
					OC_Log::write('importer',"Host/user ".$hostname.":".$real_hostname."-->".$value.":".$real_username, OC_Log::WARN);
					if($enc=="0" && $pw!="" || $value!=$real_username || $hostname!=$real_hostname){
						OC_Log::write('importer',"Updating user info ".$pr_id_host.": ".$pw.", ".$enc,OC_Log::WARN);
						if(OC_importer::updateUserInfo($real_pr_id, $real_hostname, $real_username, $pw, $enc)===false){
							$errors[$pr_id] = "ERROR: password NOT updated for ".$value.". " . mysql_error();
						}
					}
				}
			}
			else{
				if(is_numeric($pr_id)){
					OC_importer::deleteUserInfo($pr_id, $hostname);
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
