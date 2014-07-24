<?php
/**
 * Copyright (c) 2013, Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC_Util::checkAdminUser();
OCP\JSON::callCheck();

OC_Log::write('importer',"Setting provider: ".$_POST['provider'].":".$_POST['active'],OC_Log::WARN);

OC_importer::activateProvider($_POST['provider'], $_POST['active']);

echo 'true';
