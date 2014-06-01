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

require_once('apps/chooser/lib/lib_chooser.php');

//$baseuri = OC_App::getAppWebPath('downloader').'appinfo/remote.php';
$baseuri = "/remote.php/ingest";
$path = substr(OCP\Util::getRequestUri(), strlen($baseuri));

// load needed apps
$RUNTIME_APPTYPES=array('filesystem', 'authentication', 'logging');
OC_App::loadApps($RUNTIME_APPTYPES);
OC_Util::obEnd();

$lockBackend = new OC_Connector_Sabre_Locks();
$requestBackend = new OC_Connector_Sabre_Request();
$publicDir = new OC_Connector_Sabre_Directory('');
$server = new Sabre_DAV_Server($publicDir);

$user_id = OC_Chooser::checkIP();
if($user_id){
  require_once 'chooser/lib/ip_auth.php';
	OC_Log::write('downloader','user_id '.$user_id,OC_Log::WARN);
	if($user_id != '' && OC_User::userExists($user_id)){
		$_SESSION['user_id'] = $user_id;
		\OC_Util::setupFS();
	}
	$authBackend = new OC_Connector_Sabre_Auth_ip_auth();
}
else{
	$authBackend = new OC_Connector_Sabre_Auth();
}

$authPlugin = new Sabre_DAV_Auth_Plugin($authBackend,'ownCloud');//should use $validTokens here
$server->addPlugin($authPlugin);

$server->httpRequest = $requestBackend;
$server->setBaseUri($baseuri.$path);


$server->addPlugin( new Sabre_DAV_Locks_Plugin($lockBackend));
$server->addPlugin(new Sabre_DAV_Browser_Plugin(false)); // Show something in the Browser, but no upload
$server->addPlugin(new OC_Connector_Sabre_QuotaPlugin());
$server->addPlugin(new OC_Connector_Sabre_MaintenancePlugin());

$authBackend->authenticate($server, 'ownCloud');
$user = $authPlugin->getCurrentUser();

OC_Log::write('downloader',"USER: ".OC_User::getUser()." : ".$user,OC_Log::WARN);

//$server->exec();

if($user==null || trim($user)==''){
  exit -1;
}


///////////////////////////////

header("Content-Type: application/json");

$dest_dir = array_key_exists('dir', $_POST) ? $_POST['dir'] : $_GET['dir'];
$url = array_key_exists('url', $_POST) ? $_POST['url'] : $_GET['url'];
$provider = array_key_exists('provider', $_POST) ? $_POST['provider'] : $_GET['provider'];
$overwrite = array_key_exists('overwrite', $_POST) ? $_POST['overwrite'] : array_key_exists('overwrite', $_GET)?$_GET['overwrite']:TRUE;
$preserve = array_key_exists('preserve', $_POST) ? $_POST['preserve'] : array_key_exists('preserve', $_GET)?$_GET['preserve']:TRUE;
$masterpw = array_key_exists('password', $_POST) ? $_POST['password'] : array_key_exists('password', $_GET)?$_GET['password']:'';
$verbose = array_key_exists('verbose', $_POST) ? $_POST['verbose'] : array_key_exists('verbose', $_GET)?$_GET['verbose']:FALSE;

require_once('downloader/lib/downloader'.$provider.'.class.php');

$parsed_url = parse_url($url);
$pathinfo = pathinfo($parsed_url['path']);
$myprovider = 'OC_downloader'.$provider;
$l = new OC_L10N('downloader');
$dl = new $myprovider(TRUE);
$dl->getFile($url, $dest_dir, $l, $overwrite, $preserve, $masterpw, $verbose);

