<?php

OC_Util::checkAdminUser();

OCP\Util::addscript('importer', 'settings');

$tmpl = new OCP\Template( 'importer', 'settings.tpl');

$tmpl->assign( 'user_prov_set', OC_importer::getProvidersList(-1));

return $tmpl->fetchPage();
