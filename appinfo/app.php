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

OCP\App::checkAppEnabled('importer');

OC::$CLASSPATH['OC_importer'] = 'apps/importer/lib/importer.class.php';

$l = OC_L10N::get('importer');

if(!OC_importer::isInitialized()){
	OC_importer::initProviders(dirname(__FILE__) . '/providers.xml');
}

OCP\App::register(Array(
	'order' => 30,
	'id' => 'importer',
	'name' => 'importer'
));

OCP\App::addNavigationEntry(Array(
	'id' => 'importer_index',
	'order' => 30,
	'href' => OCP\Util::linkTo('importer', 'importer.php'),
	'icon' => OCP\Util::imagePath('importer', 'importer.svg'),
	'name' => 'Importer'
));

OCP\App::registerPersonal('importer', 'personalsettings');
OCP\App::registerAdmin('importer', 'settings');

$dl_dir = OC_importer::getDownloadFolder();

if(OCP\User::getUser() && strlen($dl_dir) != 0){
	$fs = OCP\Files::getStorage('files');
	if(!$fs){
		return;
	}
	if(!$fs->is_dir($dl_dir)){
		$fs->mkdir($dl_dir);
	}
}
