<?php

require_once('downloader/lib/downloader.class.php');
require_once('downloader/lib/downloaderIA.class.php');
require_once('downloader/lib/downloaderPB.class.php');

OCP\JSON::checkAppEnabled('downloader');
OCP\JSON::checkLoggedIn();

$l = new OC_L10N('downloader');

set_time_limit(0);
ini_alter("memory_limit", "1024M");
@ob_end_clean();
ob_implicit_flush(true);
ignore_user_abort(true);
clearstatcache();
error_reporting(6135);

?>
<html>
	<head>
		<style type="text/css">
			body{color:#555;font:0.6em "Lucida Grande",Arial,Verdana,sans-serif;font-weight:normal;text-shadow:0 1px 0 #FFF;margin:0 1px 0 0;overflow:hidden;}
		</style>
	</head>
	<body>
		<?php
		$dl = new OC_downloaderIA();
		echo '<div style="width:99%;">';
		$dl->pb->render();
		echo '</div>';
		$dl->pb->setText($l->t('Preparing download ...'));
		
		$pr = array_key_exists('p', $_GET)?urldecode(trim($_GET['p'])):'';
		$url = array_key_exists('u', $_GET)?urldecode(trim($_GET['u'])):'';
		$ow = array_key_exists('o', $_GET)?urldecode(trim($_GET['o'])):'';
		$kd = array_key_exists('k', $_GET)?urldecode(trim($_GET['k'])):'';
		$mp = array_key_exists('m', $_GET)?urldecode(trim($_GET['m'])):'';

		if(strcmp(substr($url,0,7), 'http://') != 0 && strcmp(substr($url,0,8), 'https://') != 0){
			$url = 'https://'.$url;
		}
		
		$purl = parse_url($url);
		if(!isset($purl['scheme']) || !isset($purl['host']) || !isset($purl['path'])){
			$dl->pb->setError($l->t('URL error ...') . ": ".$url);
		}
		else{
			$path = urlencode($purl['path']);
			$path = str_replace('%2F', '/', $path);
			//$path = str_replace('+' , '%20' , $path);
			$furl = $purl['scheme'].'://'.$purl['host'].(isset($purl['port'])?':'.$purl['port']:'').$path;
			if(!preg_match('/^pr_([0-9]{1,4})$/', $pr, $m)){
				$dl->pb->setError($l->t('Unknown provider') . ": " . $pr);
			}
			else{
				$dl->getFile($furl, '', $l, $ow, $kd, $mp, FALSE);
			}
		}
		?>
	</body>
</html>
