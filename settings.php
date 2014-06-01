<?php

OC_Util::checkAdminUser();

OCP\Util::addscript('downloader', 'settings');

$tmpl = new OCP\Template( 'downloader', 'settings.tpl');

$tmpl->assign( 'user_prov_set', OC_downloader::getProvidersList(-1));

return $tmpl->fetchPage();
