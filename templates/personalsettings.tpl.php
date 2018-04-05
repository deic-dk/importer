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

OCP\Util::addStyle('importer', 'personalsettings');

?>

<fieldset id="importerPersonalSettings" class="section">
	<h2><?php p($l->t("Data import"));?></h2>
	<input type="hidden" id="importer" name="importer" value="1" />
	<br />
	<div>
	<?php p($l->t("Credentials"));?>:
	</div>
	<br />
	<!-- fake fields are a workaround for chrome autofill getting the wrong fields -->
	<input style="display:none" type="text" name="fakeusernameremembered"/>
	<input style="display:none" type="password" name="fakepasswordremembered"/>
	<?php foreach($_['pr_list'] as $p){ ?>
	<div class="importer_pr" id="importer_pr_<?php p($p['pr_id']); ?>">
		<span style="float:left;width:100px;">
			<label title="<?php p(isset($p['pr_desc'])?$p['pr_desc']:''); ?>"><?php p($p['pr_name']); ?></label>
		</span>
		<input class="username" type="text" autocomplete="off" name="importer_pr_un_<?php p($p['pr_id']); ?>" id="importer_pr_un_<?php p($p['pr_id']); ?>" value="<?php p(!is_null($p['us_id'])?$p['us_username']:''); ?>" placeholder="<?php p($l->t('Username'));?>" />

		<input class="password" autocomplete="off" type="password" name="importer_pr_pw_<?php p($p['pr_id']); ?>" id="importer_pr_pw_<?php p($p['pr_id']); ?>" value="<?php p(!is_null($p['us_id'])?$p['us_password']:''); ?>" placeholder="<?php p($l->t('Password'));?>" data-typetoggle="#importer_pr_show_<?php p($p['pr_id']); ?>" />
		
		<input type="checkbox" id="importer_pr_show_<?php p($p['pr_id']); ?>" class="personal-show" /><label for="importer_pr_show_<?php p($p['pr_id']); ?>"></label>
		
		<input type="hidden" id="importer_pr_orig_<?php p($p['pr_id']); ?>" name="importer_pr_orig_<?php p($p['pr_id']); ?>"  placeholder="Encrypted" class="orig_enc_pw" value="<?php p(!is_null($p['us_id'])?$p['us_password']:''); ?>" />
		<input type="hidden" id="importer_pr_enc_<?php p($p['pr_id']); ?>" name="importer_pr_enc_<?php p($p['pr_id']); ?>"  placeholder="Encrypted" class="enc" value="1" />
		<?php p(!is_null($p['us_id'])?'<img class="importer-delete" src="' . OC_Helper::imagePath('importer', 'delete.png') . '" rel="' . $p['pr_id'] . '" title="'.$l->t('Clear').'" />':''); ?>
		<?php p(!array_key_exists($p['pr_id'], $_['errors'])?'':'<label class="error">'.$_['errors'][$p['pr_id']].'</label>')?>
	</div>
		<?php } ?>
	<br />
	<?php p($l->t("Download folder"));?>:	
	<input type="text" name="importer_download_folder" value="<?php p(isset($_['download_folder'])?$_['download_folder']:''); ?>" placeholder="/"/>
	<label class="importer_choose_download_folder btn btn-flat"><?php p($l->t("Browse"));?></label>
	<div id="download_folder" style="visibility:hidden;display:none;"></div>
	<div class="importer_folder_dialog" display="none">
		<div class="loadFolderTree"></div>
		<div class="file" style="visibility: hidden; display:inline;"></div>
	</div>
	<br />
	<br />
	<label id="importer_settings_submit" class="button"><?php p($l->t('Save'));?></label>&nbsp;<label id="importer_msg"></label>
</fieldset>
		
<div id="oc_pw_dialog">
<label><?php p($l->t("Master password to decrypt stored provider passwords"));?>: </label><input type="password" id="importer_pw" />
</div>
