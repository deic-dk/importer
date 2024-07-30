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
	
	<div class="importer_header">
	<span class="importer_column_1">
		<label ><?php p($l->t("Protocol"));?></label>
	</span>
	<span class="importer_column_2"">
		<label ><?php p($l->t("Hostname"));?></label>
	</span>
	<span class="importer_column_3">
		<label ><?php p($l->t("Username"));?></label>
	</span>
	<span class="importer_column_4">
		<label ><?php p($l->t("Password"));?></label>
	</span>
	</div>
	
	<!-- Add a dummy element to user_providers to use for hidden template -->
	<?php
	$_['user_providers'][] = array('pr_id'=>'0', 'pr_name'=>'', 'us_id'=>'', 'us_hostname'=>'', 'us_username'=>'', 'us_password'=>'');
	?>
	
	<?php foreach($_['user_providers'] as $p){ ?>
	
	<?php $id = $p['pr_id'].'_'.$p['us_hostname']; ?>
	
	<div class="importer_pr<?php if($id==0){?> hidden<?php }?>" id="importer_pr_<?php p($id);?>">
	
		<span class="data_provider">
		
		<select id="importer_pr_id_<?php p($id); ?>" name="importer_pr_id_<?php p($id); ?>">
			<?php foreach($_['pr_list'] as $pr){ ?>
				<option value="<?php p($pr['pr_id']);?>" <?php if($pr['pr_name']==$p['pr_name']){?>selected="selected"<?php }?>><?php p($pr['pr_name']);?></option>
			<?php }?>
		</select>

		</span>
		
		<input class="hostname" type="text"
		autocomplete="off" name="importer_pr_hn_<?php p($id); ?>"
		id="importer_pr_hn_<?php p($id); ?>" value="<?php p(!is_null($p['us_id'])?$p['us_hostname']:''); ?>"
		placeholder="<?php p($l->t('Hostname'));?>" />

		<input class="username" type="text"
		autocomplete="off" name="importer_pr_un_<?php p($id); ?>"
		id="importer_pr_un_<?php p($id); ?>" value="<?php p(!is_null($p['us_id'])?$p['us_username']:''); ?>"
		placeholder="<?php p($l->t('Username'));?>" />
		
		<input class="old_username hidden" type="text"
		autocomplete="off" name="importer_pr_on_<?php p($id); ?>"
		id="importer_pr_on_<?php p($id); ?>" value="<?php p(!is_null($p['us_id'])?$p['us_username']:''); ?>" />

		<input class="password numeric-password"
		autocomplete="off" type="text" name="importer_pr_pw_<?php p($id); ?>"
		id="importer_pr_pw_<?php p($id); ?>"
		value="<?php p(!is_null($p['us_id'])?$p['us_password']:''); ?>"
		placeholder="<?php p($l->t('Password'));?>"
		data-typetoggle="#importer_pr_show_<?php p($id); ?>" />
		
		<input type="checkbox" id="importer_pr_show_<?php p($id); ?>"
		class="personal-show" /><label for="importer_pr_show_<?php p($id); ?>"></label>
		
		<input type="hidden" id="importer_pr_orig_<?php p($id); ?>"
		name="importer_pr_orig_<?php p($id); ?>"  placeholder="Encrypted"
		class="orig_enc_pw" value="<?php p(!is_null($p['us_id'])?$p['us_password']:''); ?>" />
		<input type="hidden" id="importer_pr_enc_<?php p($id); ?>"
		name="importer_pr_enc_<?php p($id); ?>"  placeholder="Encrypted" class="enc" value="1" />
		<?php echo(!is_null($p['us_id'])?'<img class="importer-delete" src="' .
				OC_Helper::imagePath('importer', 'delete.png') . '" rel="' .
				$id . '" title="'.$l->t('Clear').'" />':''); ?>
		<?php echo(!array_key_exists($id, $_['errors'])?'':'<label class="error">'.
				$_['errors'][$id].'</label>')?>
	</div>
		<?php } ?>
	<br />
		<label class="add_importer_url btn btn-flat">+</label>
	<br />
	<?php p($l->t("Download folder"));?>:
	<input type="text" name="importer_download_folder"
	value="<?php p(isset($_['download_folder'])?$_['download_folder']:''); ?>" placeholder="/"/>
	<label class="importer_choose_download_folder btn btn-flat"><?php p($l->t("Browse"));?></label>
	<div id="download_folder" style="visibility:hidden;display:none;"></div>
	<div class="importer_folder_dialog" display="none">
		<div class="loadFolderTree"></div>
		<div class="file" style="visibility: hidden; display:inline;"></div>
	</div>
	<br />
	<br />
	<label id="importer_settings_submit" class="button"><?php p($l->t('Save'));?>
	</label>&nbsp;<label id="importer_msg"></label>
</fieldset>

<div id="oc_pw_dialog">
<label><?php p($l->t("Master password to decrypt stored provider passwords"));?>:
</label><input type="text" id="importer_pw" class="numeric-password" />
</div>
