<?php

OC_Util::checkAdminUser();

OCP\Util::addscript('ocdownloader', 'settings');

$tmpl = new OCP\Template( 'ocdownloader', 'settings.tpl');

$tmpl->assign( 'user_prov_set', OC_ocDownloader::getProvidersList(-1));

return $tmpl->fetchPage();
