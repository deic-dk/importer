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
* FTP provider file
* 
*/

require_once('importer/lib/importerFTP.class.php');

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
			$dl = new OC_importerFTP(false, $groupDir);
		}
		else{
			$dl = new OC_importerFTP();
		}
		
		echo '<div style="width:99%;">';
		$dl->pb->render();
		echo '</div>';
		$dl->pb->setText($l->t('Preparing...'));
		
		if(!OC_importerFTP::checkFTPMod()){
			$dl->pb->setError($l->t('FTP mod is disabled'));
		}
		else{
			$pr = array_key_exists('p', $_GET)?urldecode(trim($_GET['p'])):'';
			$url = array_key_exists('u', $_GET)?urldecode(trim($_GET['u'])):'';
			$ow = array_key_exists('o', $_GET)?urldecode(trim($_GET['o'])):'';
			$kd = array_key_exists('k', $_GET)?urldecode(trim($_GET['k'])):'';
			$mp = array_key_exists('m', $_GET)?urldecode(trim($_GET['m'])):'';
			$dir = array_key_exists('d', $_GET)?urldecode(trim($_GET['d'])):'';
				
			if(strcmp(substr($url,0,6), 'ftp://') != 0 && strcmp(substr($url,0,7), 'ftps://') != 0){
				$url = 'ftp://'.$url;
			}
			
			$purl = parse_url($url);
			if(!isset($purl['scheme']) || !isset($purl['host']) || !isset($purl['path'])  /*|| !isset($purl['user']) || !isset($purl['pass'])*/){
				$dl->pb->setError($l->t('Wrong URL'));
			}
			else{
				$dl->getFile($url, $dir, $l, $ow, $kd, $mp, false);
			}
		}
		?>
	</body>
</html>
