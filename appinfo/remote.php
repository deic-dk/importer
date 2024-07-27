<?php

/**
* ownCloud
*
* Original:
* @author Frank Karlitschek
* @copyright 2012 Frank Karlitschek frank@owncloud.org
* 
* Adapted:
* @author Michiel de Jong, 2011
*
* Adapted:
* @author Frederik Orellana, 2013
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
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

// curl --insecure -u fror@dtu.dk:password 'https://10.2.1.254/remote.php/ingest?provider=IA&url=https://ia801609.us.archive.org/24/items/WIDE-20120813083152-crawl422/MANIFEST.txt'

// curl -k -L --request GET -u fror@dtu.dk:password "https://data.deic.dk/remote.php/ingest/?provider=IA&url=https://ia801609.us.archive.org/24/items/WIDE-20120813083152-crawl422/WIDE-20120813090159-00699.warc.os.cdx.gz"

// curl -k -L --request GET -u fror@dtu.dk:password "https://data.deic.dk/remote.php/ingest/?provider=IA&url=https://archive.org/download/WIDE-20120813083152-crawl422/WIDE-20120813090159-00699.warc.os.cdx.gz"

// curl -k -L --request GET "https://10.2.1.254/remote.php/ingest/?provider=HTTP&url=http://ftp.funet.fi/pub/mirrors/mirror.cs.wisc.edu/pub/mirrors/ghost/contrib/gsapi_delphi.zip

// curl -k -L --request GET "https://10.2.0.254/remote.php/ingest/?provider=S3&password=test&url=s3://aws-publicdatasets/common-crawl/crawl-002/2010/01/06/0/1262850367358_0.arc.gz"

OCP\JSON::checkAppEnabled('chooser');
require_once('chooser/lib/lib_chooser.php');
require_once('chooser/lib/ip_auth.php');
require_once('chooser/lib/nbf_auth.php');

$ok = false;

if(!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])){
	$authBackendNBF = new OC_Connector_Sabre_Auth_NBF();
	$ok = $authBackendNBF->checkUserPass($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
}

if(!$ok){
	$authBackendIP = new Sabre\DAV\Auth\Backend\IP();
	$ok = $authBackendIP->checkUserPass($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
}

OCP\JSON::checkLoggedIn();

$userServerAccess = \OCA\FilesSharding\Lib::getUserServerAccess();
// Block all access if account is locked on server (or user is a two-factor user)
if($ok && \OCP\App::isEnabled('files_sharding') &&
		$userServerAccess!=\OCA\FilesSharding\Lib::$USER_ACCESS_ALL &&
		$userServerAccess!=\OCA\FilesSharding\Lib::$USER_ACCESS_READ_ONLY){
	$ok = false;
}

// Block write operations on r/o server
if($ok && \OCP\App::isEnabled('files_sharding') &&
		$userServerAccess==\OCA\FilesSharding\Lib::$USER_ACCESS_READ_ONLY &&
		(strtolower($_SERVER['REQUEST_METHOD'])=='mkcol' || strtolower($_SERVER['REQUEST_METHOD'])=='put' ||
				strtolower($_SERVER['REQUEST_METHOD'])=='move' || strtolower($_SERVER['REQUEST_METHOD'])=='delete' ||
				strtolower($_SERVER['REQUEST_METHOD'])=='proppatch')){
	$ok = false;
}

\OCP\Util::writeLog('importer', 'User '.$_SERVER['PHP_AUTH_USER']." --> ".$ok, \OC_Log::WARN);

if(!$ok){
	header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
	exit();
}

$user = OCP\USER::getUser();
$group = isset($_GET['group']) ? $_GET['group'] : '';
if(!empty($group) && !empty($user)){
	$filesDir = '/'.$user.'/user_group_admin/'.$group;
	OC_Log::write('importer','Non-files access: '.$filesDir, OC_Log::WARN);
	\OC\Files\Filesystem::tearDown();
	\OC\Files\Filesystem::init($user, $filesDir);
}
elseif(!empty($user)){
	$filesDir = '/'.$user.'/files';
	\OC\Files\Filesystem::init($user, $filesDir);
}

///////////////////////////////

require_once('apps/chooser/appinfo/apache_note_user.php');

header("Content-Type: application/json");

$dest_dir = array_key_exists('dir', $_POST) ? $_POST['dir'] : $_GET['dir'];
$url = array_key_exists('url', $_POST) ? $_POST['url'] : $_GET['url'];
$overwrite = array_key_exists('overwrite', $_POST) ? $_POST['overwrite'] : array_key_exists('overwrite', $_GET)?$_GET['overwrite']:TRUE;
$preserve = array_key_exists('preserve', $_POST) ? $_POST['preserve'] : array_key_exists('preserve', $_GET)?$_GET['preserve']:TRUE;
$masterpw = array_key_exists('password', $_POST) ? $_POST['password'] : array_key_exists('password', $_GET)?$_GET['password']:'';
$verbose = array_key_exists('verbose', $_POST) ? $_POST['verbose'] : array_key_exists('verbose', $_GET)?$_GET['verbose']:FALSE;

$parsed_url = parse_url($url);
$protocol = array_key_exists('protocol', $_REQUEST) ? $_REQUEST['protocol'] : $parsed_url['scheme'];
$protocol = strtoupper($protocol);

require_once('importer/lib/importer'.$protocol.'.class.php');

$parsed_url = parse_url($url);
$myprovider = 'OC_importer'.$protocol;
$l = new OC_L10N('importer');
$dl = new $myprovider(TRUE, $filesDir);
$dl->getFile($url, $dest_dir, $l, $overwrite, $preserve, $masterpw, $verbose);

