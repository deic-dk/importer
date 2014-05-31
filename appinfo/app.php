<?php

/**
* ownCloud - ocDownloader plugin
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

OCP\App::checkAppEnabled('ocdownloader');

OC::$CLASSPATH['OC_ocDownloader'] = 'apps/ocdownloader/lib/ocDownloader.class.php';

$l = OC_L10N::get('ocdownloader');

if(!OC_ocDownloader::isInitialized()){
	OC_ocDownloader::initProviders(dirname(__FILE__) . '/providers.xml');
}

OCP\App::register(Array(
	'order' => 30,
	'id' => 'ocdownloader',
	'name' => 'ocDownloader'
));

OCP\App::addNavigationEntry(Array(
	'id' => 'ocdownloader_index',
	'order' => 30,
	'href' => OCP\Util::linkTo('ocdownloader', 'downloader.php'),
	'icon' => OCP\Util::imagePath('ocdownloader', 'dl.png'),
	'name' => 'DL'
));

OCP\App::registerPersonal('ocdownloader', 'personalsettings');
OCP\App::registerAdmin('ocdownloader', 'settings');

$dl_dir = OC_ocDownloader::getDownloadFolder();

if(OCP\User::getUser() && strlen($dl_dir) != 0){
	$fs = OCP\Files::getStorage('files');
	if(!$fs->is_dir($dl_dir)){
		$fs->mkdir($dl_dir);
	}
}