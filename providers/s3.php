<?php

require_once('importer/lib/importer.class.php');
require_once('importer/lib/importerS3.class.php');
require_once('importer/lib/importerPB.class.php');

OCP\JSON::checkAppEnabled('importer');
OCP\JSON::checkLoggedIn();

OCP\Util::addScript('importer', 'pb');

$l = new OC_L10N('importer');

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
		<script type="text/javascript" src="/core/js/jquery-1.10.0.min.js"></script>
		<script type="text/javascript" src="/apps/importer/js/pb.js"></script>
	</head>
	<body>
		<?php
		$group = array_key_exists('g', $_GET)?urldecode(trim($_GET['g'])):'';
		if(OCP\App::isEnabled('user_group_admin') && !empty($group)){
			// Allow ingesting to group folders
			$user = \OC_User::getUser();
			\OC\Files\Filesystem::tearDown();
			$groupDir = '/'.$user.'/user_group_admin/'.$group;
			\OC\Files\Filesystem::init($user, $groupDir);
			$dl = new OC_importerS3(false, $groupDir);
		}
		else{
			$dl = new OC_importerS3();
		}
		echo '<div style="width:99%;">';
		$dl->pb->render();
		echo '</div>';
		$dl->pb->setText($l->t('Preparing...'));
		
		$pr = array_key_exists('p', $_GET)?urldecode(trim($_GET['p'])):'';
		$url = array_key_exists('u', $_GET)?urldecode(trim($_GET['u'])):'';
		$ow = array_key_exists('o', $_GET)?urldecode(trim($_GET['o'])):'';
		$kd = array_key_exists('k', $_GET)?urldecode(trim($_GET['k'])):'';
		$mp = array_key_exists('m', $_GET)?urldecode(trim($_GET['m'])):'';
		$dir = array_key_exists('d', $_GET)?urldecode(trim($_GET['d'])):'';
		$group = array_key_exists('g', $_GET)?urldecode(trim($_GET['g'])):'';
		
		if(strcmp(substr($url,0,5), 's3://') != 0 && strcmp(substr($url,0,6), 'sss://') != 0){
			$url = 's3://'.$url;
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
				$dl->getFile($furl, $dir, $l, $ow, $kd, $mp, false);
			}
		}
		?>
	</body>
</html>
