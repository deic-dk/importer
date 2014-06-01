<?php

/**
* ownCloud downloader app
*
* @author Xavier Beurois
* @copyright 2012 Xavier Beurois www.djazz-lab.net
* @extended Frederik Orellana, 2013
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

OCP\App::checkAppEnabled('downloader');
OCP\User::checkLoggedIn();

OCP\App::setActiveNavigationEntry('downloader_index');

$tmpl = new OCP\Template('downloader', 'downloader.tpl', 'user');

// Get user downloader settings
if(!in_array('curl', get_loaded_extensions())){
	$tmpl->assign('curl_error', TRUE);
}else{
	$l = new OC_L10N('downloader');
	
	$tmpl->assign('user_prov_set', OC_downloader::getProvidersList());
	$tmpl->assign('user_history', OC_downloader::getUserHistory($l));
}

$tmpl->printPage();
