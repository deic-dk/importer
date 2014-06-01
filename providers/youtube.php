<?php

/**
* ownCloud downloader app
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
* YouTube provider file
* 
*/

require_once(OC_App::getAppPath('downloader')."/lib/downloader.class.php");
require_once(OC_App::getAppPath('downloader')."/lib/downloaderYT.class.php");
require_once(OC_App::getAppPath('downloader')."/lib/downloaderPB.class.php");

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
		OC_downloaderPB::init();
		echo '<div style="width:99%;">';
		OC_downloaderPB::render();
		echo '</div>';
		OC_downloaderPB::setText($l->t('Preparing download ...'));
		
		$pr = urldecode(trim($_GET['p']));
		$url = urldecode(trim($_GET['u']));
		$ow = urldecode(trim($_GET['o']));
		$kd = urldecode(trim($_GET['k']));
		
		if(strcmp(substr($url,0,7), 'http://') != 0 && strcmp(substr($url,0,8), 'https://') != 0){
			$url = 'http://'.$url;
		}

		$purl = parse_url($url);
		if(!isset($purl['query'])){
			OC_downloaderPB::setError($l->t('Provide a good URL !'));
		}else{
			if(strcmp(substr($purl['query'],0,2),'v=') != 0){
				OC_downloaderPB::setError($l->t('Provide a good URL !'));
			}else{
				$filename = substr($purl['query'],2) . '.flv';
				OC_downloaderYT::init($url, $filename, 0, $ow, $kd);
			}
		}
		?>
	</body>
</html>
