<?php

/**
* ownCloud importer app
*
* @author Xavier Beurois
* @copyright 2012 Xavier Beurois www.djazz-lab.net
* @extended 2014 Frederik Orellana
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

/**
 * This class manages importer with the database. 
 */
class OC_importer {

	private static $saltName = 'importerSalt';
	private static $cookieName = 'importerPw';
	private static $expire = 0;//time()+60*60*24*30;
	//private static $expired = time()-3600;
	private static $path = '/';
	private static $downloadFolderTag = ':::';

	
	/**
	 * Get user provider settings
	 * @param $active get active (1) providers or inactive (0) providers
	 * @return Array
	 */
	public static function getProvidersList($active = 1){
		if($active===-1){
		  $query = OCP\DB::prepare("SELECT pr_id, pr_name, pr_active FROM *PREFIX*importer_providers");
		  $result = $query->execute(Array())->fetchAll();
		}
		else{
		  $query = OCP\DB::prepare("SELECT pr_id, pr_name FROM *PREFIX*importer_providers WHERE pr_active = ?");
		  $result = $query->execute(Array($active))->fetchAll();
		}
		if(count($result) > 0){
			return $result;
		}
		return Array();
	}
	
	/**
	 * Get provider info
	 * @param $pr_id the provider id
	 * @return Array
	 */
	public static function getProvider($pr_id){
		$query = OCP\DB::prepare("SELECT pr_id, pr_name, pr_auth FROM *PREFIX*importer_providers WHERE pr_id = ?");
		$result = $query->execute(Array($pr_id))->fetchRow();
		if($result){
			return $result;
		}
		return Array();
	}
	
	/**
	 * Add a provider (if not exists)
	 * @param $name Name of the provider
	 * @param $auth Whether or not the provider requires authentication
	 * @param $desc Description of the provider
	 */
	public static function addProvider($name, $auth, $desc){
		$query = OCP\DB::prepare("SELECT pr_id FROM *PREFIX*importer_providers WHERE pr_name = ?");
		$result = $query->execute(Array($name))->fetchRow();
		if(!$result){
			$query = OCP\DB::prepare("INSERT INTO *PREFIX*importer_providers (pr_name,pr_auth,pr_desc) VALUES (?,?,?)");
			$query->execute(Array($name,$auth,$desc));
		}
		else{
			$query = OCP\DB::prepare("UPDATE *PREFIX*importer_providers SET pr_name = ?,pr_auth = ?,pr_desc = ? WHERE pr_id = ?");
			$query->execute(Array($name,$auth,$desc,$result['pr_id']));
		}
	}

	/**
	 * Activate or deactivate a provider
	 * @param $name Name of the provider
	 * @param $active 1 for active, 0 for inactive
	 */
	public static function activateProvider($name, $active){
		$query = OCP\DB::prepare("SELECT pr_id FROM *PREFIX*importer_providers WHERE pr_name = ?");
		$result = $query->execute(Array($name))->fetchRow();
		if($result){
			$query = OCP\DB::prepare("UPDATE *PREFIX*importer_providers SET pr_active = ? WHERE pr_id = ?");
			$query->execute(Array($active,$result['pr_id']));
		}
	}
	
	/**
	 * Get User provider username and password
	 * @param $pr_id Provider id or name
	 * @param $master_pw Master password
	 * @return Array
	 */
	public static function getUserProviderInfo($pr_id, $master_pw = NULL){
		$pr_name = $pr_id;
		if(preg_match('/^[0-9]+$/', $pr_id )){
			$pr = self::getProvider($pr_id);
			$pr_name = $pr['pr_name'];
		}
		$query = OCP\DB::prepare("SELECT us_username, us_password FROM *PREFIX*importer_users_settings WHERE oc_uid = ? AND pr_fk = ?");
		$result = $query->execute(Array(OCP\User::getUser(), $pr_name))->fetchRow();
		if($result){
			$result['us_password'] = self::decryptPw($result['us_password'], $master_pw);
			return $result;
		}
		return Array();
	}
	
		/**
	 * Get User provider username and encrypted password
	 * @param $pr_id Provider id or name
	 * @return Array
	 */
	public static function getUserProviderInfoRaw($pr_id){
		$pr_name = $pr_id;
		if(preg_match('/^[0-9]+$/', $pr_id )){
			$pr = self::getProvider($pr_id);
			$pr_name = $pr['pr_name'];
		}
		$query = OCP\DB::prepare("SELECT us_username, us_password FROM *PREFIX*importer_users_settings WHERE oc_uid = ? AND pr_fk = ?");
		OC_Log::write('importer',"Query: ".OCP\User::getUser(). ":" . $pr_name, OC_Log::WARN);
		$result = $query->execute(Array(OCP\User::getUser(), $pr_name))->fetchRow();
		if($result){
			return $result;
		}
		return Array();
	}
	
	/**
	 * Get a list of providers in the database
	 * @return Array
	 */
	public static function getUserProvidersList($auth = 0, $active = 1){
		if($auth){
			$query = OCP\DB::prepare("SELECT p.pr_id, p.pr_name, u.us_id, u.us_username, u.us_password FROM *PREFIX*importer_providers p LEFT OUTER JOIN *PREFIX*importer_users_settings u ON p.pr_name = u.pr_fk AND (u.oc_uid = ? OR u.oc_uid IS NULL) WHERE p.pr_auth = ? AND p.pr_active = ?");
			$result = $query->execute(Array(OCP\User::getUser(), $auth, $active))->fetchAll();
		}else{
			$query = OCP\DB::prepare("SELECT p.pr_id, p.pr_name, u.us_id, u.us_username, u.us_password FROM *PREFIX*importer_providers p LEFT OUTER JOIN *PREFIX*importer_users_settings u ON p.pr_name = u.pr_fk AND (u.oc_uid = ? OR u.oc_uid IS NULL) WHERE p.pr_active = ?");
			$result = $query->execute(Array(OCP\User::getUser(), $active))->fetchAll();
		}
		if(count($result) > 0){
			return $result;
		}
		return Array();
	}
	
	/**
	 * Get the download folder
	 * @param $raw If us_download_folder is not set in the database, either an empty string or "/" is returned, depending on whether this is set or not
	 * @return Array
	 */
	public static function getDownloadFolder($raw = 0){
		$query = OCP\DB::prepare("SELECT u.us_download_folder FROM *PREFIX*importer_users_settings u WHERE u.oc_uid = ?");
		$result = $query->execute(Array(OCP\User::getUser()))->fetchAll();
		$folder = '';
		if(count($result) > 0){
		  $folder = trim($result[0]["us_download_folder"]);
		}
		return $folder!=''&&$folder!='null'&&$folder!=null?$folder:($raw?"":"/");
	}
	
	/**
	 * UPDATE user provider info
	 * @param $pr_id The provider name
	 * @param $username The user provider username
	 * @param $pw The user provider password
	 */
	public static function updateUserInfo($pr_id, $username, $cleartext_pw){
		$pw = self::encryptPw($cleartext_pw);
		if(!$pw){
			return false;
		}
		$pr = self::getProvider($pr_id);
		$pr_name = $pr['pr_name'];
		$str = "SELECT us_id FROM *PREFIX*importer_users_settings WHERE oc_uid = ? AND pr_fk = ?";
		$query = OCP\DB::prepare($str);
		OC_Log::write('importer', "Executing query: ".$str, OC_Log::WARN);
		$result = $query->execute(Array(OCP\User::getUser(), $pr_name))->fetchRow();
		if($result){
			$query = OCP\DB::prepare("UPDATE *PREFIX*importer_users_settings SET us_username = ?, us_password = ? WHERE oc_uid = ? AND pr_fk = ?");
			return $query->execute(Array($username, $pw, OCP\User::getUser(), $pr_name));
		}
		else{
			$query = OCP\DB::prepare("INSERT INTO *PREFIX*importer_users_settings (oc_uid,pr_fk,us_username,us_password) VALUES (?,?,?,?)");
			return $query->execute(Array(OCP\User::getUser(), $pr_name, $username, $pw));
		}
	}
	
	public static function updateDownloadFolder($downloadFolder){
	  $downloadFolder = trim($downloadFolder);
	  if($downloadFolder!='' && $downloadFolder[0]!='/'){
	    $downloadFolder = '/' . $downloadFolder;
	  }
	  $query = OCP\DB::prepare("SELECT us_download_folder FROM *PREFIX*importer_users_settings WHERE oc_uid = ? AND pr_fk = ?");
	  $result = $query->execute(Array(OCP\User::getUser(), self::$downloadFolderTag))->fetchRow();
	  OC_Log::write('importer', "Setting download folder: ".$downloadFolder, OC_Log::WARN);
	  if($result){
			if($downloadFolder==''){
				$query = OCP\DB::prepare("DELETE FROM *PREFIX*importer_users_settings WHERE oc_uid = ? AND pr_fk = ?");
				$query->execute(Array(OCP\User::getUser(), self::$downloadFolderTag));
			}
			else{
				$query = OCP\DB::prepare("UPDATE *PREFIX*importer_users_settings SET us_download_folder = ? WHERE oc_uid = ?");
				$query->execute(Array($downloadFolder, OCP\User::getUser()));
			}
	  }
	  else{
			if($downloadFolder==''){
				return;
			}
			else{
				$query = OCP\DB::prepare("INSERT INTO *PREFIX*importer_users_settings (us_download_folder,oc_uid,pr_fk) VALUES (?,?,?)");
				$query->execute(Array($downloadFolder, OCP\User::getUser(), self::$downloadFolderTag));
			}
	  }
	}
	
	/**
	 * DELETE user provider info
	 * @param $pr_id The provider id
	 */
	public static function deleteUserInfo($pr_id){
		$pr = self::getProvider($pr_id);
		$pr_name = $pr['pr_name'];
		$query = OCP\DB::prepare("SELECT us_id FROM *PREFIX*importer_users_settings WHERE oc_uid = ? AND pr_fk = ?");
		$result = $query->execute(Array(OCP\User::getUser(), $pr_name))->fetchAll();
		if(count($result) > 0){
			$query = OCP\DB::prepare("DELETE FROM *PREFIX*importer_users_settings WHERE us_id = ?");
			foreach($result as $row){
				$query->execute(Array($row['us_id']));
			}
		}
	}
	
	/**
	 * Check if providers have been initialized.
	 * @return boolean
	 */
	public static function isInitialized(){
		$ini_file = OC_App::getAppPath('importer')."/.reinitialize";
		if(file_exists($ini_file)){
			unlink($ini_file);
			return false;
		}
		$active_providers = self::getProvidersList(1);
		$inactive_active_providers = self::getProvidersList(0);
		$initialized = !empty($active_providers) || !empty($inactive_active_providers);
		return $initialized;
	}
	
	/**
	 * Deactivate (in database) providers that no longer exist on disk
	 */
	public static function purgeProviders($file){
	  $db_providers = self::getProvidersList();
	  $xml = new DOMDocument();
	  $xml->load($file);
	  $xml_providers = $xml->getElementsByTagName('provider');
	  foreach($db_providers as $db_prov){
	    $found = false;
	    foreach($xml_providers as $xml_prov){
	      if($db_prov['pr_name'] == $xml_prov->getElementsByTagName('name')){
				$found = true;
				break;
	      }
	    }
	    if(!$found){
	      self::activateProvider($db_prov['pr_name'], 0);
	    }
	  }
	}
	
	/**
	 * Initialize providers list
	 * @param $file Providers file list
	 */
	public static function initProviders($file){
		self::purgeProviders($file);
		$xml = new DOMDocument();
		$xml->load($file);
		$providers = $xml->getElementsByTagName('provider');
		foreach($providers as $provider){
			$name_key = $provider->getElementsByTagName('name');
		  	$name_val = $name_key->item(0)->nodeValue;
			$auth_key = $provider->getElementsByTagName('auth');
		  	$auth_val = $auth_key->item(0)->nodeValue;
			$desc_key = $provider->getElementsByTagName('desc');
		  	$desc_val = $desc_key->item(0)->nodeValue;
		  	self::addProvider($name_val, $auth_val, $desc_val);
		}
	}
	
	/**
	 * Get user tasks
	 * @param $l Lang
	 * @return Array if results or FALSE
	 */
	public static function getUserHistory($l){
		$query = OCP\DB::prepare("SELECT * FROM *PREFIX*importer WHERE oc_uid = ? ORDER BY dl_ts DESC");
		$results = $query->execute(Array(OCP\User::getUser()))->fetchAll();
		
		if(count($results) > 0){
			foreach($results as $key => $result){
				$results[$key]['dl_ts'] = date($l->t('d/m-Y H:i:s'), $results[$key]['dl_ts']);
			}
			return $results;
		}
		return FALSE;
	}
	
	/**
	 * Set User task
	 * @param $file The downloaded file
	 * @param $status The downloaded file status
	 */
	public static function setUserHistory($file, $status){
		$query = OCP\DB::prepare("INSERT INTO *PREFIX*importer (oc_uid,dl_ts,dl_file,dl_status) VALUES (?,?,?,?)");
		$query->execute(Array(OCP\User::getUser(),time(),$file,$status));
	}
	
	/**
	 * Clear history of user tasks
	 */
	public static function clearUserHistory(){
		OC_Log::write('importer', "Clearing history", OC_Log::WARN);
		$query = OCP\DB::prepare("DELETE FROM *PREFIX*importer WHERE oc_uid = ?");
		$query->execute(Array(OCP\User::getUser()));
	}

	public static function mktmpdir() {
	    $uid = uniqid("oc_wget_");
	    $tempdir = sys_get_temp_dir();
	    $tempdir = $tempdir . (substr($tempdir,-1)==='/'?"":"/") . $uid;
	    if(mkdir($tempdir)){
	      return $tempdir;
	    }
	}

	/* Password encryption/decryption. */

	private static function getSessionPwHash(){
		if (!isset($_SESSION[self::$saltName])) {
			$_SESSION[self::$saltName] = md5(uniqid(rand(), true));
			OC_Log::write('importer', "Session PW hash not set", OC_Log::WARN);
		}
		OC_Log::write('importer', "Session PW hash :".$_SESSION[self::$saltName], OC_Log::WARN);
		return $_SESSION[self::$saltName];
	}

	private static function my_encrypt($enc, $salt){
		$ret = openssl_encrypt($enc, 'aes-128-cbc', $salt);
		OC_Log::write('importer', "Encrypted ".$enc." with ".$salt." to ".$ret, OC_Log::WARN);
		return $ret;
	}
	
	private static function my_decrypt($enc, $salt){
		if($enc===""){
			return "";
		}
		$ret = openssl_decrypt($enc, 'aes-128-cbc', $salt);
		OC_Log::write('importer', "Decrypted ".$enc." with salt ".$salt." to ".$ret, OC_Log::WARN);
		return $ret;
	}
	
	public static function storeMasterPw($master_pw){
		$cookiedomain = \OCP\Config::getSystemValue('cookiedomain', '');
		$sessionPwHash = self::getSessionPwHash();
		$encMasterPw = self::my_encrypt($master_pw, $sessionPwHash);
		$ret = setcookie(self::$cookieName, $encMasterPw, self::$expire, self::$path, $cookiedomain, false, false);
		OC_Log::write('importer', "Stored master password  ".$encMasterPw." in cookie with name ".self::$cookieName, OC_Log::WARN);
		return $ret;
	}
	
	public static function encryptPw($pw){
		$sessionPwHash = self::getSessionPwHash();
		if(!array_key_exists(self::$cookieName, $_COOKIE)){
			return false;
		}
		$encMasterPw = $_COOKIE[self::$cookieName];
		$masterPw = self::my_decrypt($encMasterPw, $sessionPwHash);
		$hashedMasterPw = md5($masterPw);
		$ret = self::my_encrypt($pw, $hashedMasterPw);
		return $ret;
	}

	public static function getMasterPw(){
		$sessionPwHash = self::getSessionPwHash();
		$encMasterPw = $_COOKIE[self::$cookieName];
		OC_Log::write('importer', "Trying to decrypt encrypted master pw ".$encMasterPw, OC_Log::WARN);
		$masterPw = self::my_decrypt($encMasterPw, $sessionPwHash);
		return $masterPw;
	}
	
	public static function decryptPw($enc_pw, $master_pw=false){
		$masterPw = $master_pw?$master_pw:self::getMasterPw();
		$hashedMasterPw = md5($masterPw);
		$ret = self::my_decrypt($enc_pw, $hashedMasterPw);
		OC_Log::write('importer', "Decrypted ".$enc_pw." with ".$masterPw." to ".$ret, OC_Log::WARN);
		if($ret==='' && $enc_pw!==''){
			return null;
		}
		return $ret;
	}

}
