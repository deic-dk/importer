<?php

require_once __DIR__ . '/../../../lib/base.php';

OC_Util::checkAdminUser();
//OCP\JSON::callCheck();

OC_Log::write('importer',"Setting provider: ".$_POST['provider'].":".$_POST['active'],OC_Log::WARN);

OC_importer::activateProvider($_POST['provider'], $_POST['active']);

echo 'true';
