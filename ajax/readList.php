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
*/

OCP\JSON::checkAppEnabled('importer');
OCP\JSON::checkLoggedIn();

$file_name = $_POST['file_name'];

$fs = OCP\Files::getStorage('files');

$full_name = "/".$file_name;

if(!($ret=json_decode($fs->file_get_contents($full_name)))){
	$ret['error'] = "Failed reading from ".$full_name;
}

OC_Log::write('importer',"Reading list ".$full_name, OC_Log::WARN);

OCP\JSON::encodedPrint($ret);
