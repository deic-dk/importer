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
 * This class manages importer FTP downloads. 
 */
 
require_once('files/cache/updater.php');
require_once('importer/lib/importerPB.class.php');

class OC_importerFTP {
	
	private $conn;
	public $pb;
	private $batch;
	
	function __construct($b = false, $filesDir = null) {
		$this->batch = $b;
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
	}

	function __destruct() {
  }
	
	/**
	 * Check FTP MOD
	 * @return Boolean
	 */
	public static function checkFTPMod(){
		if(!in_array('ftp', get_loaded_extensions())){
			return FALSE;
		}
		return TRUE;
	}
	
	/**
	 * Connect to FTP Server
	 * @param $scheme FTP protocol (ftp|ftps)
	 * @param $host The FTP host
	 * @param $user The login
	 * @param $pwd The password
	 * @return Boolean
	 */
	private function connectToHost($scheme, $host, $port, $user, $pwd){
	
		if(!$port){
			$port = 21;
		}
		if(strcmp($scheme,'ftps') == 0){
			$this->conn = ftp_ssl_connect($host, $port);
		}
		else{
			$this->conn = ftp_connect($host, $port);
		}
		
		$loginResult = ftp_login($this->conn, $user, $pwd);
		if($this->conn && $loginResult){
			ftp_pasv($this->conn, TRUE);
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * Check if the URL is a folder or not
	 * @param $path The file path
	 * @return Boolean
	 */
	private function isDir($path){
		$chDir = @ftp_chdir($this->conn, $path);
		if($chDir){
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * List directory contents recursively.
	 * @param $folderurl The URL of the directory whose content will be listed
	 * @param $user_info credentials array (see importer.class.php)
	 * @return array of paths. Each line should contain a file
	 * 				 path, relative to $url and have a / at the end of directory names.
	 */
	public function lsDir($folderurl, $user_info){
		// wget -r ftp://ftp@ftp.funet.fi/pub/mirrors/mirror.cs.wisc.edu/pub/mirrors/ghost/contrib/
		// Unfortunately this does not work with all servers - notably not ftp://ftp-trace.ncbi.nlm.nih.gov/1000genomes/ftp/data/HG00096/
    //$cmd = "/usr/local/bin/ncftpls -gg ".$user_str." ".$password_str." ".$folderurl." | grep -v '/$' | sed 's|@$||' | sed 's|^|".$folderurl."|'";
    //OC_Log::write('importer',"Executing; ".$cmd, OC_Log::WARN);
    //exec($cmd, $out, $ret);
		// So, we use a custom script

		$user_str = "";
		$password_str = "";
		if(!empty($user_info)){
			$user_str = isset($user_info['us_username'])?"-u '".$user_info['us_username']."'":"";
			$password_str = isset($user_info['us_password'])?"-p '".$user_info['us_password']."'":"";
		}

		$out = array();
    $cmd = OC_App::getAppPath('importer')."/lib/ftpfind.sh ".$user_str." ".$password_str." '".$folderurl."'";
    OC_Log::write('importer',"Executing; ".$cmd, OC_Log::WARN);
    exec($cmd, $out, $ret);
		return $out;
	}
	
	/**
	 * Get file
	 * @param $rurl The URL of the file to be downloaded
	 * @param $dir The local directory
	 * @param $l Lang
	 * @param $overwrite Overwrite the target file
	 * @param $preserveDir Keep remote directory structure
	 * @param $masterpw Master password for the key store
	 * @param $verbose
	 */
	public function getFile($rurl, $dir, $l, $overwrite = false, $preserveDir = false,
			$masterpw = NULL, $verbose = false){
		try{
		
			$user = "";
			$pass = "";
			
			$url = parse_url($rurl);
			$port = 0;
			if(isset($url['port'])){
				$port = $url['port'];
			}
			if(isset($url['user'])){
				$user = $url['user'];
			}
			if(isset($url['pass'])){
				$pass = $url['pass'];
			}
			if(isset($url['host'])){
				$host = $url['host'];
			}
			
			$user_info = OC_importer::getUserProviderInfo('FTP', $host, $masterpw);
			if(isset($user_info['us_username'])){
				$user = $user_info['us_username'];
				$pass = $user_info['us_password'];
			}
			
			if(!$this->connectToHost($url['scheme'], $host, $port, $user, $pass)){
				throw new Exception($l->t('Connection failed'));
			}
			
			$rpath = preg_replace('/^\/+/', '', $url['path']);
			if($this->isDir($rpath)){
				throw new Exception($url['path']." ".$l->t('not a file'));
			}
			$size = $this->getRemoteFileSize($rpath);
			if($size<=0){
				throw new Exception($l->t('File size is').' '.$size);
			}
			OC_Log::write('importer','Size: '.$size, OC_Log::WARN);
			
			//$fs = OCP\Files::getStorage();
			OC_Log::write('importer',"Downloading to ".$this->filesDir, OC_Log::WARN);
			$fs = new \OC\Files\View($this->filesDir);
			
			$dl_dir = empty($dir)?OC_importer::getDownloadFolder():( $dir[0]==='/'?$dir:OC_importer::getDownloadFolder()."/".$dir);
						
			$rpathinfo = pathinfo(urldecode($url['path']));
			$filename = $rpathinfo['basename'];

			if($preserveDir){
				$dirs = explode("/", $rpathinfo['dirname']);
				$mydir = $dl_dir;
				foreach($dirs as $dir){
					$mydir = $mydir . "/" . $dir;
					if(!$fs->file_exists($mydir)){
						OC_Log::write('importer','Creating: '.$mydir, OC_Log::WARN);
						$fs->mkdir($mydir, 0755, true);
					}
				}
				$dl_dir = $dl_dir . "/" . $rpathinfo['dirname'];
				$dl_dir = preg_replace('/\/\/+/', '/', $dl_dir);
				if(!$fs->is_dir($dl_dir)){
					throw new Exception($l->t('Could not create directory '.$dl_dir));
				}
			}

			if($fs->file_exists($dl_dir . "/" . $filename) && !$overwrite){
				$filename = md5(rand()) . '_' . $filename;
			}

			$chunkSize = self::getChunkSize($size);
			
			$received = $last = 0;
			$start_time = microtime(true);
			$ret = ftp_nb_get($this->conn, $fs->getLocalFile($dl_dir . "/" . $filename), $rpath, FTP_BINARY);
			while($ret == FTP_MOREDATA){
				$received += $fs->filesize($dl_dir . "/" . $filename);
				if($received >= $size){
					$percent = 100;
				}
				else{
					$percent = @round(($received/$size)*100, 2);
				}
				if($received > $last + $chunkSize){
					if(!$this->batch){
						$this->pb->setProgressBarProgress($percent);
					}
					else{
						$verbose && print($percent."%\n");
					}
					$last = $received;
				}
				usleep(100);
			  $ret = ftp_nb_continue($this->conn);
			}
			$cacheUpdater = new \OC\Files\Cache\Updater($fs);
			$cacheUpdater->update($dl_dir . "/" . $filename);
			$end_time = microtime(true);
			$spent_time = $end_time-$start_time;
			$mbps = $size/$spent_time/(pow(10, 6));
			if($ret != FTP_FINISHED){
				throw new Exception($l->t('Download error'));
			}
			else{
				OC_importer::setUserHistory($rurl, 1);
				if(!$this->batch){
					$this->pb->setProgressBarProgress(100);
					OC_Log::write('importer','Finished FTP download of '.$rurl.' Size: '.$size.
						" bytes, time: ".$spent_time." s, speed: ".$mbps." MB/s", OC_Log::WARN);
				}
				else{
					$verbose && print("Done (size: ".$size." bytes, time: ".$spent_time." s, speed: ".$mbps." MB/s)\n");
				}
			}
		}
		catch(exception $e){
			if(!$this->batch){
				$this->pb->setError($e->getMessage());
			}
			try{
				$this->closeConnection();
			}
			catch(exception $ee){
			}
			throw $e;
		}
		try{
			$this->closeConnection();
		}
		catch(exception $ee){
		}
	}
	
	/**
	 * Get remote filesize
	 * @param $path The file path
	 * @return Integer
	 */
	private function getRemoteFileSize($path){
		return ftp_size($this->conn, $path);;
		/*$res = ftp_size($this->conn, $path);
		if($res != -1){
		    return $res;
		}
		return 0;*/
	}
	
	/**
	 * Close connection
	 */
	private function closeConnection(){
		ftp_close($this->conn);
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
	
}
