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

OCP\Util::addStyle('importer', 'styles');
OCP\Util::addScript('3rdparty','chosen/chosen.jquery.min');
OCP\Util::addStyle('3rdparty','chosen/chosen');

OCP\Util::addScript('importer', 'dls');
OCP\Util::addScript('importer', 'browse');

OCP\Util::addStyle('chooser', 'jqueryFileTree');
OCP\Util::addscript('chooser', 'jquery.easing.1.3');
OCP\Util::addscript('chooser', 'jqueryFileTree');

?>

<div class="titleblock">
	<span><?php p($l->t("Destination"));?>: </span>
	<?php if(OCP\App::isEnabled('user_group_admin')){ ?>
	<select id=user_groups_move_select><option value="home"><?php p($l->t("Home"));?></option></select>
	<?php } ?>
	<span class="urlc" title="<?php p($l->t("Destination folder"));?>">
		<input type="text" name="importer_download_folder" class="url"
		value="<?php p(isset($_['download_folder'])?$_['download_folder']:''); ?>"
		placeholder="folder" />
		<label class="importer_choose_download_folder btn btn-flat"><?php p($l->t("Browse"));?></label>
		<div id="download_folder" style="visibility:hidden;display:none;"></div>
		<div class="importer_folder_dialog" display="none">
			<div class="loadFolderTree"></div>
			<div class="file" style="visibility: hidden; display:inline;"></div>
		</div>
	</span>
	<?php if(!isset($_['curl_error']) && !isset($_['todl'])){ ?>
  <span class="dlbtn">
  	<button class="btn btn-primary btn-flat" id="geturl" title="<?php p($l->t('Import all files on list'));?>"><?php p($l->t('Import'));?></button>
    <button class="btn btn-default btn-flat" id="clearList" title="<?php p($l->t('Clear list of files'));?>"><?php p($l->t('Clear'));?></button>
    <button class="btn btn-default btn-flat" id="savelist" title="<?php p($l->t('Save list to file'));?>"><?php p($l->t('Save'));?></button>
    <button class="btn btn-default btn-flat" id="chooselist" title="<?php p($l->t('Load list from saved file'));?>"><?php p($l->t('Load'));?></button>
    <button class="btn btn-default btn-flat" id="getfolderurl" title="<?php p($l->t('List remote directory'));?>"><?php p($l->t('Scan URL'));?></button>
  </span>
  <div class='clear'></div>
	<?php } ?>
	<div class="clear"></div>
</div>
<div id='gallery' class="hascontrols"></div>
<div id="importer">

<div id="folder_pop">
	<div id="elt_0" class="elts folder">
			<select class="chzen-select" title="<?php p($l->t('Choose data provider / protocol'));?>" data-placeholder="<?php p($l->t('Data source'));?>">
					<option value="0"></option>
					<?php foreach($_['user_prov_set'] as $prov){ ?>
					<option value="pr_<?php p($prov['pr_id']); ?>"><?php p($prov['pr_name']);?></option>
					<?php } ?>
			</select>
			<span class="slider-frame" title="<?php p($l->t('Keep directory structure'));?>">
				<span class="slider-button"><?php p($l->t('Flat'));?></span>
			</span>
			<input type="checkbox" value="0" class="slider-check" />
		<span class="urlc" title="<?php p($l->t('URL of the folder to download')); ?>">
			<input id="folderurl" type="text" class="url" value="" placeholder="<?php p($l->t('Folder URL')); ?>" />
		</span>
		<span class="load" title="<?php p($l->t('List content of folder')); ?>">
			<button id="loadFolder"><?php p($l->t('Scan')); ?></button>
		</span>
		<span class="dling"></span>
	</div>
</div>

<div id="save_pop">
	<div id="save_list" class="elts folder">
		<span class="urlc" title="<?php p($l->t('Type name and hit enter to save')); ?>">
			<input id="urllist" type="text" class="url" value="" placeholder="<?php p($l->t('Filename')); ?>" />
			<button id="save_list" class="btn btn-flat"><?php p($l->t('Save'));?></button>
		</span>
		<span class="dling"></span>
	</div>
</div>

<div id="dialog0" title="<?php p($l->t('Choose file')); ?>">
</div>
<div id="chosen_file"></div>

	<?php if(isset($_['curl_error'])){ ?>
	<div class="personalblock red">
		<?php p($l->t('The application needs the <strong>PHP cURL</strong> extension to be loaded !')); ?>
	</div>
	<?php }else{ ?>
		<div id="dllist" class="personalblock">
			<div id="elt_1" class="elts new">
					<select class="chzen-select" title="<?php p($l->t('Choose data provider / protocol'));?>" data-placeholder="<?php p($l->t('Data source')); ?>">
						<option value="0"></option>
						<?php foreach($_['user_prov_set'] as $prov){ ?>
						<option value="pr_<?php p($prov['pr_id']); ?>"><?php p($prov['pr_name']); ?></option>
						<?php } ?>
					</select>
				<span title="<?php p($l->t('Keep directory structure'));?>" class="slider-frame">
					<span class="slider-button"><?php p($l->t('Flat'));?></span>
				</span>
				<input type="checkbox" value="0" class="slider-check" />
				<span class="urlc" title="<?php p($l->t('URL of the file to download')); ?>">
					<input type="text" class="url" value="" placeholder="<?php p($l->t('File URL')); ?>" />
				</span>
				<button class="addelt" title="<?php p($l->t('Add another download'));?>">+</button>
				<span class="dling"></span>
		</div>
	</div>
		<div id="hiddentpl">
				<select class="chzen-select" title="<?php p($l->t('Choose data provider / protocol'));?>" data-placeholder="<?php p($l->t('Data source'));?>">
					<option value="0"></option>
					<?php foreach($_['user_prov_set'] as $prov){ ?>
					<option value="pr_<?php p($prov['pr_id']); ?>"><?php p($prov['pr_name']);?></option>
					<?php } ?>
				</select>
				<span class="slider-frame" title="<?php p($l->t('Keep directory structure'));?>">
					<span class="slider-button"><?php p($l->t('Flat'));?></span>
				</span>
				<input type="checkbox" value="0" class="slider-check" />
			<span class="urlc" title="<?php p($l->t('URL of the file to download')); ?>">
				<input type="text" class="url" value="" placeholder="<?php p($l->t('File URL'));?>" />
			</span>
			<button class="eltdelete" title="<?php p($l->t('Remove this download'));?>">-</button>
			<button class="addelt" title="<?php p($l->t('Add another download'));?>">+</button>
			<span class="dling"></span>
		</div>
	<?php } ?>
	<div id="divhisto" class="personalblock">
		<?php $status = Array($l->t('Unknown error'),$l->t('OK')); 
		print($l->t('Download history')); ?>&nbsp;
		<i id="toggle_history" class="icon-angle-right" title="<?php p($l->t('Toggle history'));?>"></i>
		<button id="clear_history" title="<?php p($l->t('Clear import history'));?>"
		class="btn btn-flat ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only"
		role="button" aria-disabled="false"><?php p($l->t('Clear'));?></button>
		<table id="importer_history" border="0" cellpadding="0" cellspacing="0" style="display:none;">
			<thead>
				<tr>
					<th class="col1"><?php p($l->t('File'));?></th>
					<th class="col2"><?php p($l->t('Date / Time'));?></th>
					<th class="col3"><?php p($l->t('Status'));?></th>
				</tr>
			</thead>
			<tbody id="tbhisto">
			<?php if(!$_['user_history']){ ?>
			<tr>
				<td colspan="4"><?php p($l->t('No history for now'));?></td>
			</tr>
			<?php }else{ 
				foreach($_['user_history'] as $history){ ?>
				<tr>
					<td class="col1"><?php p($history['dl_file']); ?></td>
					<td class="col2"><?php p($history['dl_ts']); ?></td>
					<td class="col3"><?php p($status[$history['dl_status']]); ?></td>
				</tr>
				<?php }
			} ?>
			</tbody>
		</table>
	</div>
<br />
<div id="oc_pw_dialog">
<label class="nowrap"><?php p($l->t('Master password to decrypt stored provider passwords'));?>: </label><input type="password" id="importer_pw" />
</div>
