<?php

require_once('ocdownloader/lib/ocDownloaderHTTP.class.php');

/**
 * This class manages ocDownloader HTTP downloads from the Internet Archive. 
 */
 
 // wget --no-check-certificate --delete-after --save-cookies test-cookie.txt https://archive.org/account/login.php
 // wget --no-check-certificate --cookies=on --keep-session-cookies --load-cookies test-cookie.txt --delete-after --save-cookies cookies.txt --post-data 'username=[USER]%40[DOMAIN]&password=[PASSWORD]&remember=CHECKED&referer=https%3A%2F%2Farchive.org%2F&action=login&submit=Log+in' https://archive.org/account/login.php
 // wget --no-check-certificate --cookies=on --keep-session-cookies --load-cookies cookies.txt -H -nc -np -nH --cut-dirs=2 --no-directories -l 1 -e robots=off

// https://archive.org/download/WIDE-20120813083152-crawl422/
 
class OC_ocDownloaderIA extends OC_ocDownloaderHTTP {

	protected static $PROVIDER_NAME = 'IA';
	private static $TEST_COOKIE_FILE = NULL;
	private static $COOKIE_FILE = NULL;

	function __construct($b = false) {
		parent::__construct($b);
		self::setCookieFiles();
  }
  
  private static function setCookieFiles(){
  	$fs = self::getStorage();
  	self::$TEST_COOKIE_FILE = $fs->getLocalFile(OC_ocDownloader::getDownloadFolder()."/.ia-test-cookie.txt");
  	self::$COOKIE_FILE = $fs->getLocalFile(OC_ocDownloader::getDownloadFolder()."/.ia-cookies.txt");
  }

  private static function cookie_login($user_info){

  	$user_info['us_username'] = str_replace('@', '%40', $user_info['us_username']);

    shell_exec("wget --no-check-certificate --delete-after --save-cookies ".self::$TEST_COOKIE_FILE." https://archive.org/account/login.php");
    $cmd = "wget --no-check-certificate --cookies=on --keep-session-cookies --load-cookies ".self::$TEST_COOKIE_FILE." --delete-after --save-cookies ".self::$COOKIE_FILE." --post-data 'username=".$user_info['us_username']."&password=".$user_info['us_password']."&remember=CHECKED&referer=https%3A%2F%2Farchive.org%2F&action=login&submit=Log+in' https://archive.org/account/login.php";
    OC_Log::write('ocDownloader',"Executing: ".$cmd, OC_Log::WARN);
    shell_exec($cmd);

  }

	/**
	 * List directory contents recursively.
	 * This method is of limited use, since parent directories of r.g.
	 * https://archive.org/download/WIDE-20120813083152-crawl42/
	 * are not readable.
	 * @param $folderurl The URL of the directory whose content will be listed
	 * @param $user_info credentials array (see ocDownloader.class.php)
	 * @return array of paths. Each line should contain a file
	 * 				 path, relative to $url and have a / at the end of directory names.
	 */
	public static function lsDir($folderurl, $user_info){
		self::setCookieFiles();
		$out = array();
		$tmpdir = OC_ocDownloader::mktmpdir();
		# First check for a redirect
		$cmd = "/usr/local/bin/wget --no-check-certificate --cookies=on --keep-session-cookies --load-cookies ".self::$COOKIE_FILE." -P $tmpdir -e robots=off --server-response $folderurl 2>&1 | grep ' Location:' | sed -r 's|^  *Location: *([^ ]*) *$|\\1|'";
		exec($cmd, $out, $ret);
		if(!empty($out)){
			OC_Log::write('ocDownloader', "Redirect: ".$out[0], OC_Log::WARN);
			$folderurl = $out[0];
		}
		# Crawl using index.html files
		$cmd = "/usr/local/bin/wget --no-check-certificate --cookies=on --keep-session-cookies --load-cookies ".self::$COOKIE_FILE." -P $tmpdir -r -l 5 -nH --cut-dirs=5 --no-parent --spider --reject='index.html\*' -e robots=off --server-response $folderurl 2>&1 | grep '^--' | grep -v '/?' | grep -v '/$' | sed 's|.* http://|http://|' | sed 's|.* https://|https://|' | grep  '^http'";
		OC_Log::write('ocDownloader', "Executing: ".$cmd, OC_Log::WARN);
		exec($cmd, $out, $ret);
		if(empty($out)){
			self::cookie_login($user_info);
			exec($cmd, $out, $ret);
		}
		shell_exec("rmdir ".$tmpdir);
		return $out;
	}

	protected static function getAuthHeader($user_info){
		self::setCookieFiles();
		$cmd = "grep 'logged-in' '".self::$COOKIE_FILE . "' | awk 'BEGIN{ORS=\"; \"} {print \$(NF-1)\"=\"\$NF}'";
		exec($cmd, $out, $ret);
		OC_Log::write('ocDownloader',"Cookies: ".join($out), OC_Log::WARN);
		return "Cookie: ".trim(join($out));
	}
	

	protected static function execURL($url, $user_info = NULL, $head = TRUE){
			$url = str_replace(Array(" ", "\r", "\n"), Array("%20"), $url);
			$ch = curl_init($url);
			self::setopts($ch, $user_info);
			OC_Log::write('ocDownloader','Checking: '.$url, OC_Log::WARN);
			$res = curl_exec($ch);
		  $info = curl_getinfo($ch);
		  curl_close($ch);
		  if($info['http_code']>400){
		  	self::cookie_login($user_info);
		  	$ch = curl_init($url);
		  	self::setopts($ch, $user_info);
		  	OC_Log::write('ocDownloader','Checking: '.$url, OC_Log::WARN);
		  	$res = curl_exec($ch);
		    curl_close($ch);
		  }
		  return $res;
	}

	private static function setopts($ch, $user_info){
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
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Accept-Language: en-us;q=0.7,en;q=0.3", "Accept-Charset: utf-8,windows-1251;q=0.7,*;q=0.7", "Pragma: no-cache", "Cache-Control: no-cache", "Connection: Close", "User-Agent: ".self::$USER_AGENT, self::getAuthHeader($user_info)));
	}

}