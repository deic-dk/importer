<?php

require_once('importer/lib/importerHTTP.class.php');

/**
 * This class manages importer HTTP downloads from the Internet Archive. 
 */
 
 // wget --no-check-certificate --delete-after --save-cookies test-cookie.txt https://archive.org/account/login.php
 // wget --no-check-certificate --cookies=on --keep-session-cookies --load-cookies test-cookie.txt --delete-after --save-cookies cookies.txt --post-data 'username=[USER]%40[DOMAIN]&password=[PASSWORD]&remember=CHECKED&referer=https%3A%2F%2Farchive.org%2F&action=login&submit=Log+in' https://archive.org/account/login.php
 // wget --no-check-certificate --cookies=on --keep-session-cookies --load-cookies cookies.txt -H -nc -np -nH --cut-dirs=2 --no-directories -l 1 -e robots=off

// https://archive.org/download/WIDE-20120813083152-crawl422/
 
class OC_importerIA extends OC_importerHTTP {

	protected static $PROVIDER_NAME = 'IA';
	private static $TEST_COOKIE_FILE = NULL;
	private static $COOKIE_FILE = NULL;

	function __construct($b = false, $filesDir = null) {
		parent::__construct($b);
		$this->setCookieFiles();
		if(empty($filesDir)){
			$this->filesDir = '/'.\OCP\USER::getUser().'/files';
		}
		else{
			$this->filesDir = $filesDir;
		}
	}

	private function setCookieFiles(){
		\OCP\Util::writeLog('importer', 'Storing cookies in '.$this->filesDir, \OC_Log::WARN);
		//$fs = self::getStorage('files');
		$fs = new \OC\Files\View($this->filesDir);
		self::$TEST_COOKIE_FILE = $fs->getLocalFile(OC_importer::getDownloadFolder()."/.ia-test-cookie.txt");
		self::$COOKIE_FILE = $fs->getLocalFile(OC_importer::getDownloadFolder()."/.ia-cookies.txt");
	}

	private static function cookie_login($user_info){

		$user_info['us_username'] = str_replace('@', '%40', $user_info['us_username']);

		shell_exec("wget --no-check-certificate --delete-after --save-cookies ".self::$TEST_COOKIE_FILE." https://archive.org/account/login.php");
		$cmd = "wget --no-check-certificate --cookies=on --keep-session-cookies --load-cookies ".self::$TEST_COOKIE_FILE." --delete-after --save-cookies ".self::$COOKIE_FILE." --post-data 'username=".$user_info['us_username']."&password=".$user_info['us_password']."&remember=CHECKED&referer=https%3A%2F%2Farchive.org%2F&action=login&submit=Log+in' https://archive.org/account/login.php";
		OC_Log::write('importer',"Executing: ".$cmd, OC_Log::WARN);
		shell_exec($cmd);

	}

	private static function exec_unix_bg ($cmd) {
		// Executes $cmd in the background and returns the PID as an integer
		$tempdir = sys_get_temp_dir();
		$scriptFile = tempnam($tempdir, "oc_importer_");
		$outFile = tempnam($tempdir, "oc_importer_");
		OC_Log::write('importer', "Writing $cmd to script file $scriptFile", OC_Log::WARN);
		file_put_contents($scriptFile, $cmd);
		exec("chmod +x ".$scriptFile);
		$pid = exec($scriptFile.' > '.$outFile.' 2>&1 & echo $!');
		return array('pid'=>$pid, 'outfile'=>$outFile);
	}
	
	private static function pid_exists ($pid) {
		// Checks whether a process with ID $pid is running
		// There is probably a better way to do this
		return (bool) trim(exec('ps auxw | grep " '.$pid.' " | grep -v grep'));
	}
	
	private static function exec_abortable($cmd){
		// Start the program
		$pidAndOutfile = self::exec_unix_bg($cmd);
		$pid = $pidAndOutfile['pid'];
		$outfile = $pidAndOutfile['outfile'];
		OC_Log::write('importer', "Started ".$pid.", ".$outfile, OC_Log::WARN);

		// Ignore user aborts to allow us to dispatch a signal to the child
		ignore_user_abort(1);
		$size = 0;
		
		// Loop until the program completes
		while(self::pid_exists($pid)){
			
			// This is for tailing
			// - from http://stackoverflow.com/questions/1102229/how-to-watch-a-file-write-in-php
			/*clearstatcache();
			$currentSize = filesize($outfile);
			if($size!=$currentSize){
				$fh = fopen($outfile, "r");
				fseek($fh, $size);
				while($d = fgets($fh)){
					echo $d;
				}
				fclose($fh);
				$size = $currentSize;
			}*/
			// Push harmless data to the client
			echo " ";
			flush();
			// Check whether the client has disconnected
			OC_Log::write('importer', "Running... ".$pid, OC_Log::WARN);
			if(connection_aborted()){
				//OC_Log::write('importer', "Killing ".$pid, OC_Log::WARN);
				//use ps to get all the children of this process, and kill them
				$pids = preg_split('/\s+/', exec('pgrep -P '. $pid.' | grep -v '.$pid.'| sort -rn'));
				foreach($pids as $pi) {
					if(is_numeric($pi)) {
						OC_Log::write('importer', "Killing ".$pi, OC_Log::WARN);
						posix_kill($pi, 15);
					}
				}
				//posix_kill($pid, 15); // Or SIGKILL, or whatever
				break;
			}
			else{
				sleep(1);
			}
		}
		return file($outfile);
	}

	/**
	 * List directory contents recursively.
	 * This method is of limited use, since parent directories of r.g.
	 * https://archive.org/download/WIDE-20120813083152-crawl42/
	 * are not readable.
	 * @param $folderurl The URL of the directory whose content will be listed
	 * @param $user_info credentials array (see importer.class.php)
	 * @return array of paths. Each line should contain a file
	 * 				 path, relative to $url and have a / at the end of directory names.
	 */
	public function lsDir($folderurl, $user_info){
		$this->setCookieFiles();
		$out = array();
		$tmpdir = OC_importer::mktmpdir();
		
		# First check for a redirect
		$cmd = "/usr/local/bin/wget --no-check-certificate --cookies=on --keep-session-cookies --load-cookies ".self::$COOKIE_FILE." -P $tmpdir -e robots=off --server-response $folderurl 2>&1 | grep ' Location:' | sed -r 's|^  *Location: *([^ ]*) *$|\\1|'";
		exec($cmd, $out, $ret);
		if(!empty($out)){
			OC_Log::write('importer', "Redirect: ".$out[0], OC_Log::WARN);
			$folderurl = $out[0];
		}
		
		# Crawl using index.html files
		$cmd = "/usr/local/bin/wget --no-check-certificate --cookies=on --keep-session-cookies --load-cookies ".self::$COOKIE_FILE." -P $tmpdir -r -l 5 -nH --cut-dirs=5 --no-parent --spider --reject='index.html\*' -e robots=off --server-response $folderurl 2>&1 | grep '^--' | grep -v '/?' | grep -v '/$' | sed 's|.* http://|http://|' | sed 's|.* https://|https://|' | grep  '^http'";
		OC_Log::write('importer', "Executing: ".$cmd, OC_Log::WARN);
		
		$out = self::exec_abortable($cmd);
		
		if(!connection_aborted() && empty($out)){
			self::cookie_login($user_info);
			$out = self::exec_abortable($cmd);
		}
		shell_exec("rmdir ".$tmpdir);
		return $out;
	}

	protected function getAuthHeader($user_info){
		$this->setCookieFiles();
		$cmd = "grep 'logged-in' '".self::$COOKIE_FILE . "' | awk 'BEGIN{ORS=\"; \"} {print \$(NF-1)\"=\"\$NF}'";
		exec($cmd, $out, $ret);
		OC_Log::write('importer',"Cookies: ".join($out), OC_Log::WARN);
		return "Cookie: ".trim(join($out));
	}
	

	protected function execURL($url, $user_info = NULL, $head = TRUE){
			$url = str_replace(Array(" ", "\r", "\n"), Array("%20"), $url);
			$ch = curl_init($url);
			$this->setopts($ch, $user_info);
			OC_Log::write('importer','Checking: '.$url, OC_Log::WARN);
			$res = curl_exec($ch);
			$info = curl_getinfo($ch);
			curl_close($ch);
			if($info['http_code']>400){
				self::cookie_login($user_info);
				$ch = curl_init($url);
				$this->setopts($ch, $user_info);
				OC_Log::write('importer','Error received: '.$info['http_code'].'. Trying again with cookie for '.$url, OC_Log::WARN);
				$res = curl_exec($ch);
				curl_close($ch);
			}
			return $res;
	}

	private function setopts($ch, $user_info){
				curl_setopt($ch, CURLOPT_HEADER, TRUE);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
				curl_setopt($ch, CURLOPT_FAILONERROR, FALSE);
				curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
				curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
				curl_setopt($ch, CURLOPT_NOBODY, TRUE);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); 
				curl_setopt($ch, CURLOPT_TIMEOUT, 120);
				curl_setopt($ch, CURLOPT_HTTPHEADER, 
					Array("Accept-Language: en-us;q=0.7,en;q=0.3",
					"Accept-Charset: utf-8,windows-1251;q=0.7,*;q=0.7",
					"Pragma: no-cache", "Cache-Control: no-cache",
					"Connection: Close",
					"User-Agent: ".self::$USER_AGENT,
					$this->getAuthHeader($user_info)));
	}

}
