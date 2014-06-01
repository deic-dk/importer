<?php

/**
* ownCloud downloader app
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

OCP\Util::addStyle('downloader', 'personalsettings');

?>

<form autocomplete="off" id="downloader">
	<fieldset class="personalblock">
		<strong>Download</strong>
		<input type="hidden" id="downloader" name="downloader" value="1" />
		<br />
		Credentials
		<br />
		<?php foreach($_['pr_list'] as $p){ ?>
		<div class="downloader_pr" id="downloader_pr_<?php print($p['pr_id']); ?>">
			<div style="float:left;width:100px;margin-top:8px;margin-left:20px;">
				<label><?php print($p['pr_name']); ?></label>
			</div>
			<input type="text" name="downloader_pr_un_<?php print($p['pr_id']); ?>" id="downloader_pr_un_<?php print($p['pr_id']); ?>" value="<?php print(!is_null($p['us_id'])?$p['us_username']:''); ?>" placeholder="Username" />

			<input class="password" type="password" name="downloader_pr_pw_<?php print($p['pr_id']); ?>" id="downloader_pr_pw_<?php print($p['pr_id']); ?>" value="<?php print(!is_null($p['us_id'])?$p['us_password']:''); ?>" placeholder="Password" data-typetoggle="#downloader_pr_show_<?php print($p['pr_id']); ?>" />
			<input type="checkbox" id="downloader_pr_show_<?php print($p['pr_id']); ?>" class="personal-show" /><label for="downloader_pr_show_<?php print($p['pr_id']); ?>"></label>
			
			<input type="hidden" id="downloader_pr_orig_<?php print($p['pr_id']); ?>" name="downloader_pr_enc_<?php print($p['pr_id']); ?>"  placeholder="Encrypted" class="orig_enc_pw" value="<?php print(!is_null($p['us_id'])?$p['us_password']:''); ?>" />
			<input type="hidden" id="downloader_pr_enc_<?php print($p['pr_id']); ?>" name="downloader_pr_enc_<?php print($p['pr_id']); ?>"  placeholder="Encrypted" class="enc" value="1" />
			<?php print(!is_null($p['us_id'])?'<img class="downloader-delete" src="' . OC_Helper::imagePath('downloader', 'delete.png') . '" rel="' . $p['pr_id'] . '" title="'.$l->t('Clear').'" />':''); ?>
			<?php print(!array_key_exists($p['pr_id'], $_['errors'])?'':'<label class="error">'.$_['errors'][$p['pr_id']].'</label>')?>
		</div>
			<?php } ?>

		Download folder:
		<input type="text" id="downloader_download_folder" name="downloader_download_folder" value="<?php print(!is_null($_['us_download_folder'])?$_['us_download_folder']:''); ?>" placeholder="/Download"/>
		<br />
		<label id="downloader_settings_submit" class="button">Save</label>
	</fieldset>
		
</form>

<div id="oc_pw_dialog">
<label class="nowrap">Master password to decrypt stored provider passwords: </label><input type="password" id="downloader_pw" />
</div>
