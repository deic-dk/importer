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

require_once('files/cache/updater.php');

/**
 * This class manages importer HTTP downloads. 
 */
class OC_importerHTTP {

	public $pb;
	protected $batch;
	protected static $USER_AGENT = 'curl/7.21.2 (i386-pc-win32) libcurl/7.21.2 OpenSSL/0.9.8o zlib/1.2.5';
	protected static $PROVIDER_NAME = 'HTTP';
	protected $filesDir;
	private $cafile;
	private static $WGET = "/usr/local/bin/wget";
	
	function __construct($b = FALSE, $filesDir = null) {
		$this->batch = $b;
		OC_Log::write('importer',"Batch: ".$this->batch, OC_Log::WARN);
		if(!$this->batch){
			require_once('importerPB.class.php');
			$this->pb = new OC_importerPB();
		}
		if(empty($filesDir)){
			$this->filesDir = '/'.\OCP\USER::getUser().'/files';
		}
		else{
			$this->filesDir = $filesDir;
		}
		$this->cafile = \OCP\Config::getSystemValue('wscacertificate', '');
	}

	function __destruct() {
  }

	protected static function getStorage($filesDir = 'files') {
		$user = OC_User::getUser();
		$view = new \OC\Files\View('/'.$user);
		if(!$view){
			return null;
		}
		if(!$view->file_exists($filesDir)) {
			$view->mkdir($filesDir);
		}
		return new \OC\Files\View('/'.$user.'/'.$filesDir);
	}

	/**
	 * List directory contents recursively.
	 * @param $folderurl The URL of the directory whose content will be listed
	 * @param $user_info credentials array (see importer.class.php)
	 * @return array of paths. Each line should contain a file
	 * 				 path, relative to $url and have a / at the end of directory names.
	 */
	public function lsDir($folderurl, $user_info){
	
		// wget -r -nH --cut-dirs=$dirs --no-parent --reject="index.html*" -e robots=off --server-response http://ftp.funet.fi/pub/mirrors/mirror.cs.wisc.edu/pub/mirrors/ghost/contrib/

		$user_str1 = "";
		$password_str1 = "";
		$user_str = "";
		$password_str = "";
		if(!empty($user_info)){
		  $user_str1 = isset($user_info['us_username'])?"--user='".$user_info['us_username']."'":"";
		  $password_str1 = isset($user_info['us_password'])?"--password='".$user_info['us_password']."'":"";
			$user_str = isset($user_info['us_username'])?"-u '".$user_info['us_username']."'":"";
			$password_str = isset($user_info['us_password'])?"-p '".$user_info['us_password']."'":"";
		}

		$out = array();
		# First try if webdav is supported
		$cmd = OC_App::getAppPath('importer')."/lib/davfind.sh ".$user_str." ".$password_str." '".$folderurl."'";
		OC_Log::write('importer',"Executing; ".$cmd, OC_Log::WARN);
		exec($cmd, $out, $ret);

		# Next check if we can crawl using index.html files
		if(empty($out)){
			$tmpdir = OC_importer::mktmpdir();
			$cmd = self::$WGET." --no-check-certificate ".$user_str1." ".$password_str1." -P $tmpdir -r -l 5 -nH --cut-dirs=5 --no-parent --spider --reject='index.html\*' -e robots=off --server-response '".$folderurl."' 2>&1 | grep -r '^--' | grep -rv '/?' | grep -v '/$' | sed 's|.* http://|http://|' | sed 's|.* https://|https://|' | grep  '^http'";
			OC_Log::write('importer',"Executing; ".$cmd, OC_Log::WARN);
			exec($cmd, $out, $ret);
			shell_exec("rmdir ".$tmpdir);
		}
		return $out;
	}

	protected function getAuthHeader($user_info){
		$b64 = base64_encode($user_info['us_username'].":".$user_info['us_password']);
		return "Authorization: Basic $b64";
	}
	
	/**
	 * Get the file by a URL
	 * @param $url URL of the file
	 * @param $dir Path of the receiving directory for the  downloaded file, (relative to the download folder if not starting with /)
	 * @param $l Lang
	 * @param $overwrite Overwrite the target file
	 * @param $preserveDir Keep remote directory structure
	 * @param $masterpw Master password for the key store
	 * @param $verbose Whether or not to report progress
	 */
	public function getFile($url, $dir, $l, $overwrite = 'auto', $preserveDir = false,
			$masterpw = null, $verbose = false){

		 ini_set('user_agent', self::$USER_AGENT);

		try{
			if(preg_match('/^file:.+$/', $url)){
				throw new Exception("Security violation.");
			}
			
			//$fs = self::getStorage();
			$fs = new \OC\Files\View($this->filesDir);
			
			$dl_dir = strlen($dir)==0?OC_importer::getDownloadFolder():( $dir[0]==='/'?$dir:OC_importer::getDownloadFolder()."/".$dir);
			OC_Log::write('importer','Getting: '.$url, OC_Log::WARN);
			$parsed_url = parse_url($url);
			// When downloading from a standard apache with index.html in directories, the url path typically reflects the
			// path on the hard disk which you want to replicate.
			// We allow for overriding this with a path GET parameter in the url.
			// Notice that we assume the path argument will be urlencoded
			$rpathinfo = pathinfo(urldecode($parsed_url['path']));
			$dirName = $rpathinfo['dirname'];
			if(!empty($parsed_url['query'])){
				parse_str($parsed_url['query'], $get_array);
				if(!empty($get_array['path'])){
					$dirName = urldecode($get_array['path']);
				}
			}
			$filename = $rpathinfo['basename'];

			if($preserveDir){
				$dirs = explode("/", $dirName);
				$mydir = $dl_dir;
				foreach($dirs as $dir){
					$mydir = $mydir . "/" . $dir;
					if(!$fs->file_exists($mydir)){
						OC_Log::write('importer','Creating: '.$mydir, OC_Log::WARN);
						$fs->mkdir($mydir, 0755, true);
					}
				}
				$dl_dir = $dl_dir . "/" . $dirName;
				//$dl_dir = str_replace('~', '_', $dl_dir);
				$dl_dir = preg_replace('/\/\/+/', '/', $dl_dir);
				if(!$fs->is_dir($dl_dir)){
					throw new Exception($l->t('Could not create directory '.$dl_dir));
				}
			}

			$purl = parse_url($url);

			$user_info = OC_importer::getUserProviderInfo(static::$PROVIDER_NAME, $purl['host'], $masterpw);

			
			$cookieHeader = "";
			// Reuse possible auth cookies if we're getting from ourself
			OC_Log::write('importer','Looking for cookie: '.$purl['host'].'-->'.self::isInMyDomain($purl['host']).':'.serialize($_COOKIE), OC_Log::WARN);
			if(self::isInMyDomain($purl['host']) && !preg_match('|^'.OC::$WEBROOT.'/public/'.'*|', $purl['path']) && !preg_match('|^'.OC::$WEBROOT.'/shared/'.'*|', $purl['path'])){
				$instanceId = \OC_Config::getValue('instanceid', null);
				$sessionCookie = $_COOKIE[$instanceId];
				$cookieHeader = "Cookie: ".$instanceId."=".$sessionCookie;
				if(!empty($_COOKIE['public_link_authenticated'])){
					$cookieHeader .= "\nCookie: public_link_authenticated=".$_COOKIE['public_link_authenticated'];
				}
				OC_Log::write('importer','Using cookie: '.$cookieHeader, OC_Log::WARN);
			}

			$code = 0;
			if(!$this->checkFileAccess($url, $code, $user_info)){
				if(!$this->checkFileAccess($url, $code, $user_info, FALSE)){
					throw new Exception((array_key_exists($code, self::$http_codes)?self::$http_codes[$code]:"Unknown return code: ".$code));
				}
			}

			$check = $this->checkRemoteFile($url, $user_info, true, $cookieHeader);
			$size = $check['size'];
			if($size==0){
				$check = $this->checkRemoteFile($url, $user_info, false, $cookieHeader);
				$size = $check['size'];
				if($size==0){
					//throw new Exception($l->t('File size is 0. ').$url);
					// /storage does not send any content-length header...
					OC_Log::write('importer','File size is 0: '.$url, OC_Log::WARN);
				}
			}

			$location =  $check['location'];
			if(!empty($location)){
				$url = $location;
			}
			
			$skip_file = FALSE;
			if($fs->file_exists($dl_dir . "/" . $filename)){
				if(!$overwrite){
					$filename = md5(rand()) . '_' . $filename;
				}
				elseif($overwrite==='auto' && $fs->filesize($dl_dir . "/" . $filename)===$size){
					OC_Log::write('importer','Already downloaded and ok. URL: '.$url. ", DIR: ".$dl_dir. ", PATH: ".$dl_dir. "/" . $filename. ", PRESERVEDIR: ".$preserveDir, OC_Log::WARN);
					$skip_file = TRUE;
				}
				else{
				  OC_Log::write('importer','Redownloading. URL: '.$url. ", DIR: ".$dl_dir. ", PATH: ".$dl_dir. "/" . $filename. ", PRESERVEDIR: ".$preserveDir." Size: ".$fs->filesize($dl_dir . "/" . $filename)."!=".$size, OC_Log::WARN);
				}
			}
			$purl = parse_url($url);
			OC_Log::write('importer','URL: '.$url. ", DIR: ".$dl_dir. ", PATH: ".$dl_dir. "/" . $filename.
					", PRESERVEDIR: ".$preserveDir.", SIZE: ".$size.", COOKIE: ".$cookieHeader, OC_Log::WARN);
			
			$context = NULL;
			if(preg_match('/^(https*:\/\/)([^@]+):([^@]+)@(.+)$/', $url, $m)){
				$url = $m[1].$m[4];
				$user_info['us_username'] = $m[2];
				$user_info['us_password'] = $m[3];
			}
			if(preg_match('/^(https*):\/\/([^@]+)$/', $url, $m) && !empty($user_info) && !isset($purl['user']) && isset($user_info['us_username'])){
				$auth = $this->getAuthHeader($user_info);
				$opts = array (
					'http' => array(
					'method' => 'GET',
					'header' => array($auth."\r\n",
														$cookieHeader."\r\n",
														"If-Modified-Since: Sun, 10 May 1900 02:01:00 GMT"),
					'user_agent' => self::$USER_AGENT,
					'follow_location' => 1,
					'max_redirects' => 10,
				 ),
				 'ssl'=>array(
				 	'verify_peer' => false,
				 	'verify_peer_name' => false,
				 	'cafile' => $this->cafile
				 )
				);
			}
			else{
				$opts = array (
					'http' => array (
					'method' => 'GET',
					'user_agent' => self::$USER_AGENT,
					'follow_location' => 1,
					'max_redirects' => 10,
					'header' => array($cookieHeader."\r\n",
														"If-Modified-Since: Sun, 10 May 1900 02:01:00 GMT\r\n")
					),
					'ssl'=>array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'cafile' => $this->cafile
					)
				);
			}
			$context = stream_context_create($opts);

			//$randomStr = md5(uniqid(rand(), true));
			// I guess the below was to prevent getting cached files
			//- but it also prevents getting files from https://sciencedata.dk/public/...
			//$url = $url.(strpos($url, '?')!==false?'&':'?').$randomStr;
			OC_Log::write('importer', 'Opening '.$url, OC_Log::WARN);
			if(!($fp = fopen($url, 'rb', false, $context))){
				throw new Exception('Failed opening URL' . stream_get_meta_data($fp));
			}
			
			// Use filename from header if present
			$realfilename = "";
			if(!empty($http_response_header)){
				foreach($http_response_header as $header){
					if(preg_match("|^Content-Disposition:.*[; ]filename=['\"]([^'\"]+)['\"]|", $header, $filename_str)){
						$realfilename = urldecode($filename_str[1]);
						OC_Log::write('importer', 'Got header '.$url.'-->'.$header.'-->'.$realfilename, OC_Log::WARN);
						break;
					}
				}
				if(!empty($realfilename) && !empty(trim(trim($realfilename, "/"))) && $fs->file_exists($dl_dir . "/" . $realfilename)){
					if(!$overwrite){
						$realfilename = md5(rand()) . '_' . $realfilename;
						$fs->rename($dl_dir . "/" . $filename, $dl_dir . "/" . $realfilename);
					}
					elseif($overwrite==='auto' && $fs->filesize($dl_dir . "/" . $realfilename)===$size){
						OC_Log::write('importer','Already downloaded and ok. URL: '.$url. ", DIR: ".$dl_dir. ", PATH: ".$dl_dir. "/" . $realfilename. ", PRESERVEDIR: ".$preserveDir, OC_Log::WARN);
						$fs->unlink($dl_dir . "/" . $filename);
					}
					else{
						OC_Log::write('importer','Redownloading. URL: '.$url. ", DIR: ".$dl_dir. ", PATH: ".$dl_dir. "/" . $realfilename. ", PRESERVEDIR: ".$preserveDir." Size: ".$fs->filesize($dl_dir . "/" . $filename)."!=".$size, OC_Log::WARN);
						if(!empty($realfilename) && !empty($filename) && !empty($dl_dir)){
							$fs->rename($dl_dir . "/" . $filename, $dl_dir . "/" . $realfilename);
						}
					}
				}
				if(!empty($realfilename)){
					$filename = $realfilename;
				}
			}

			$fh = $fs->fopen($dl_dir . "/" . $filename, 'w');
			$chunkSize = self::getChunkSize($size);

			$received = $last = 0;
			$start_time = microtime(TRUE);

			OC_Log::write('importer', 'Opened '.$url.':'.serialize(stream_get_meta_data($fp)), OC_Log::DEBUG);

			if(!$skip_file){
				while(!feof($fp)){
					OC_Log::write('importer', 'Reading '.$chunkSize, OC_Log::INFO);
					$data = @fread($fp, $chunkSize);
					if($skip_file || $data===null || $data===false || $data==='' /*|| $data==='null'*/){
						OC_Log::write('importer', 'Breaking here: '.$data, OC_Log::WARN);
						break;
					}
					$saved = fwrite($fh, $data);
					if($saved > -1){
						$received += $saved;
					}
					if($received >= $size){
						$percent = 100;
					}
					else{
						$percent = @round(($received/$size)*100, 2);
					}
					if($received > $last + $chunkSize){
						if(!$this->batch){
							$this->pb->setProgressBarProgress($percent);
							//OC_Log::write('importer', $percent, OC_Log::WARN);
						}
						else{
							if($verbose){
								print($percent."%\n");
								flush();
							}
						}
						$last = $received;
					}
					usleep(100);
				}
			}
			OC_Log::write('importer', 'Done. Updating cache for '.$dl_dir . "/" . $filename, OC_Log::DEBUG);
			
			$cacheUpdater = new \OC\Files\Cache\Updater($fs);
			$cacheUpdater->update($dl_dir . "/" . $filename);
			$end_time = microtime(TRUE);
			$spent_time = $end_time-$start_time;
			$mbps = $size/$spent_time/(pow(10, 6));
			OC_importer::setUserHistory($url, 1);
			if(!$this->batch){
				$this->pb->setProgressBarProgress(100);
			}
			else{
				print(($skip_file?"Skipped":"Done")." (size: ".$size." bytes, time: ".$spent_time." s, speed: ".$mbps." MB/s, chunksize: ".$chunkSize.")\n");
			}
			fclose($fp);
			fclose($fh);
		}
		catch(exception $e){
			if(!$this->batch){
				$this->pb->setError($e->getMessage());
			}
			else{
				print $e->getMessage()."\n";
			}
		}
	}

	/**
	 * cURL session
	 * @param $url The URL to be executed with curl
	 * @param $user_info User info for authentication
	 * @param $head whether or not to use HEAD instead of GET
	 * @return The cURL result
	 */
	protected function execURL($url, $user_info = NULL, $head = TRUE, $cookieHeader=""){
		$url = str_replace(Array(" ", "\r", "\n"), Array("%20"), $url);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_FAILONERROR, FALSE);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
		$headerArray =  Array("Accept-Language: en-us;q=0.7,en;q=0.3",
				"Accept-Charset: utf-8,windows-1251;q=0.7,*;q=0.7", "Pragma: no-cache",
				"Cache-Control: no-cache", "Connection: Close", "User-Agent: ".self::$USER_AGENT);
		if(!empty($cookieHeader)){
			$headerArray[] = $cookieHeader;
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
		curl_setopt($ch, CURLOPT_NOBODY, $head);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); 
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		if($user_info == NULL){
			$user_info = OC_importer::getUserProviderInfo('HTTP');
		}
		$purl = parse_url($url);
		if(preg_match('/^(https*:\/\/)([^@]+):([^@]+)@(.+)$/', $url, $m)){
			$url = $m[1].$m[4];
			$user_info['us_username'] = $m[2];
			$user_info['us_password'] = $m[3];
		}
		if(!empty($user_info) && isset($user_info['us_username'])){
			OC_Log::write('importer','Using auth: '.$user_info['us_username'].":".$user_info['us_password'], OC_Log::WARN);
			//curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, TRUE);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ; 
			curl_setopt($ch, CURLOPT_USERPWD, $user_info['us_username'].":".$user_info['us_password']);
		}
		OC_Log::write('importer','Checking: '.$url, OC_Log::WARN);
		$res = curl_exec($ch);
	  curl_close($ch);
	  return $res;
	}

	/**
	 * Get size of the remote file
	 * @param $remoteFile The remote file URL
	 * @param $user_info User info for authentication
	 * @param $head whether or not to use HEAD instead of GET
	 * @return Int Size of the remote file 
	 */
	private function checkRemoteFile($remoteFile, $user_info, $head = true, $cookieHeader=""){
		$data = $this->execURL($remoteFile, $user_info, $head, $cookieHeader);
		if($data===false){
			return [];
		}
		$contentLength = -1;
		$location = "";
		// Grab the last Content-Length + Location header
		foreach(preg_split("/((\r?\n)|(\r\n?))/", $data) as $line){
			if(preg_match('/^Content-Length: (\d+)$/i', $line, $m)){
				$contentLength = (int)$m[1];
			}
			if(preg_match('/^Location: (.+)$/i', $line, $m)){
				if(!empty(trim($m[1]))){
					$location = trim($m[1]);
				}
			}
		}
		OC_Log::write('importer','Checked: '.$remoteFile.'-->'.$contentLength.'-->'.$location.'-->'.$cookieHeader, OC_Log::WARN);
		return ['size'=>$contentLength, 'location'=>$location];
	}

	/**
	 * Check file Access
	 * @param $url The file URL
	 * @param $code Return code
	 * @param $user_info User info for authentication
	 * @param $head whether or not to use HEAD instead of GET
	 * @return Boolean
	 */
	private function checkFileAccess($url, &$code, $user_info, $head = TRUE){
		OC_Log::write('importer','Checking rights of: '.$url, OC_Log::WARN);
		$code = 'unknown';
		$data = $this->execURL($url, $user_info, $head);
		if($data === false){
			return FALSE;
		}
		$ret = FALSE;
		if(preg_match('/^HTTP\/1\.[01] (\d\d\d)/', $data, $m)){
			$code = (int)$m[1];
			if($code<400){
				$ret = TRUE;
			}
			/*switch($code){
				case 200:
					$ret = TRUE;
					break; 
				case 403:
					$ret = FALSE;
					break;
				default:
					$ret = FALSE;
			}*/
		}
		return $ret;
	}
	
	/**
	 * Get the chunk size according to the total size
	 */
	private static function getChunkSize($fsize){
	    if($fsize <= 1024*1024){
	        return 4096;
	    }
	    if($fsize <= 1024*1024*10){
	        return 4096*10;
	    }
	    if($fsize <= 1024*1024*40){
	        return 4096*30;
	    }
	    if($fsize <= 1024*1024*80){
	        return 4096*47;
	    }
	    if($fsize <= 1024*1024*120){
	        return 4096*65;
	    }
	    if($fsize <= 1024*1024*150){
	        return 4096*70;
	    }
	    if($fsize <= 1024*1024*200){
	        return 4096*85;
	    }
	    if($fsize <= 1024*1024*250){
	        return 4096*100;
	    }
	    if($fsize <= 1024*1024*300){
	        return 4096*115;
	    }
	    if($fsize <= 1024*1024*400){
	        return 4096*135;
	    }
	    if($fsize <= 1024*1024*500){
	        return 4096*170;
	    }
	    if($fsize <= 1024*1024*1000){
	        return 4096*200;
	    }
	    return 4096*210;
	}
	
	private static function isInMyDomain($hostname){
		if(empty($hostname)){
			return false;
		}
		$mydomain = preg_replace('|^[^\.]+\.|', '', $_SERVER['HTTP_HOST']);
		$mydomain1 = preg_replace('|^[^\.]+\.|', '', $_SERVER['SERVER_NAME']);
		OC_Log::write('importer','Domain check: '.$mydomain.':'.$mydomain1.':'.$hostname, OC_Log::WARN);
		return $hostname==$mydomain || preg_match('|.*\.'.$mydomain.'$|', $hostname) || preg_match('|.*\.'.$mydomain1.'$|', $hostname);
	}
	
	private static $http_codes = array(
			0 => 'Unknown error',
	    100 => 'Continue',
	    101 => 'Switching Protocols',
	    102 => 'Processing',
	    200 => 'OK',
	    201 => 'Created',
	    202 => 'Accepted',
	    203 => 'Non-Authoritative Information',
	    204 => 'No Content',
	    205 => 'Reset Content',
	    206 => 'Partial Content',
	    207 => 'Multi-Status',
	    300 => 'Multiple Choices',
	    301 => 'Moved Permanently',
	    302 => 'Found',
	    303 => 'See Other',
	    304 => 'Not Modified',
	    305 => 'Use Proxy',
	    306 => 'Switch Proxy',
	    307 => 'Temporary Redirect',
	    400 => 'Bad Request',
	    401 => 'Unauthorized',
	    402 => 'Payment Required',
	    403 => 'Forbidden',
	    404 => 'Not Found',
	    405 => 'Method Not Allowed',
	    406 => 'Not Acceptable',
	    407 => 'Proxy Authentication Required',
	    408 => 'Request Timeout',
	    409 => 'Conflict',
	    410 => 'Gone',
	    411 => 'Length Required',
	    412 => 'Precondition Failed',
	    413 => 'Request Entity Too Large',
	    414 => 'Request-URI Too Long',
	    415 => 'Unsupported Media Type',
	    416 => 'Requested Range Not Satisfiable',
	    417 => 'Expectation Failed',
	    418 => 'I\'m a teapot',
	    422 => 'Unprocessable Entity',
	    423 => 'Locked',
	    424 => 'Failed Dependency',
	    425 => 'Unordered Collection',
	    426 => 'Upgrade Required',
	    449 => 'Retry With',
	    450 => 'Blocked by Windows Parental Controls',
	    500 => 'Internal Server Error',
	    501 => 'Not Implemented',
	    502 => 'Bad Gateway',
	    503 => 'Service Unavailable',
	    504 => 'Gateway Timeout',
	    505 => 'HTTP Version Not Supported',
	    506 => 'Variant Also Negotiates',
	    507 => 'Insufficient Storage',
	    509 => 'Bandwidth Limit Exceeded',
	    510 => 'Not Extended'
	);
}
