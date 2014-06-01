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
*/

OCP\JSON::checkAppEnabled('downloader');
OCP\JSON::checkLoggedIn();

$file_name = $_POST['file_name'];
$list = $_POST['list'];
$overwrite = $_POST['overwrite'];


$dl_dir = OC_downloader::getDownloadFolder();
$fs = OCP\Files::getStorage('files');

$full_name = $dl_dir."/".$file_name;
if($fs->file_exists($full_name) && $overwrite!="true"){
	$full_name = $dl_dir . "/". md5(rand()) . '_' . $file_name;
}

OC_Log::write('downloader',"Saving list ".$full_name." - overwrite: ".$overwrite.", content: ".$list, OC_Log::WARN);

if(!$fs->file_put_contents($full_name, $list)){
	$ret['error'] = "Failed saving to ".$full_name;
}
else{
	$ret['msg'] = "Saved list to ".$full_name;
}

OCP\JSON::encodedPrint($ret);
