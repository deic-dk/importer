<?php

/**
* ownCloud importer app
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

OCP\App::checkAppEnabled('importer');
OCP\User::checkLoggedIn();

OCP\App::setActiveNavigationEntry('importer_index');

$tmpl = new OCP\Template('importer', 'importer.tpl', 'user');

// Get user importer settings
if(!in_array('curl', get_loaded_extensions())){
	$tmpl->assign('curl_error', TRUE);
}
else{
	$l = new OC_L10N('importer');
	$tmpl->assign('user_prov_set', OC_importer::getProvidersList());
	$tmpl->assign('user_history', OC_importer::getUserHistory($l));
	$tmpl->assign('download_folder', OC_importer::getDownloadFolder());
	$tmpl->assign('get_url', OC_importer::getGetUrl());
	$tmpl->assign('post_urls', OC_importer::getPostUrls());
}

$tmpl->printPage();
