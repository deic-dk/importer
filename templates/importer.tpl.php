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
OCP\Util::addStyle('files', 'files');
OCP\Util::addScript('3rdparty','chosen/chosen.jquery.min');
OCP\Util::addStyle('3rdparty','chosen/chosen');

OCP\Util::addScript('importer', 'dls');
OCP\Util::addScript('importer', 'extra'); // temporary javascript added by Christian 
OCP\Util::addScript('importer', 'browse');

OCP\Util::addStyle('chooser', 'jqueryFileTree');
OCP\Util::addscript('chooser', 'jquery.easing.1.3');
OCP\Util::addscript('chooser', 'jqueryFileTree');

?>
<div id="app-content-importer" class="viewcontainer">
<div id="controls" style="max-height:63px;min-height:63px;">
	<div class="row">
      <div class="col-sm-5">
				<div id="destination">
				  <span>Destination folder</span>
				  <span class="urlc">
					<input type="text" name="importer_download_folder" class="url" value="<?php print(isset($_['download_folder'])?$_['download_folder']:''); ?>" placeholder="<?php print($l->t('/')); ?>" />
				  </span>
				  
					<label class="importer_choose_download_folder btn btn-default btn-flat">Browse</label>
					<div id="download_folder" style="visibility:hidden;display:none;"></div>
					<div class="importer_folder_dialog" display="none">
						<div class="loadFolderTree"></div>
						<div class="file" style="visibility: hidden; display:inline;"></div>
					</div>  
	
	</div>
	  </div>
	  
	  <div class="col-sm-7 text-right">

		<?php if(!isset($_['curl_error']) && !isset($_['todl'])){ ?>
		  <div class="actions creatable">
		    <button id="clearList"    class="btn btn-default btn-flat"><?php print($l->t('Clear all')); ?></button>
		    <button id="savelist"     class="btn btn-default btn-flat"><?php print($l->t('Save to file')); ?></button>
		    <button id="chooselist"   class="btn btn-default btn-flat"><?php print($l->t('Load from file')); ?></button>
		    <button id="showhist"     class="btn btn-default btn-flat" style="min-width:108px"><?php print($l->t('Show history')); ?></button>
		  </div>
		  <div class='clear'></div>
		  <?php } ?>
		  <div class="clear"></div>
		</div>
	  </div>
	</div>
	<!--	<div id='gallery' class="hascontrols"></div>-->
	<div id="importer">

	  <div id="folder_pop">
		<div id="elt_0" class="elts folder">
		  <select class="chzen-select" title="<?php print($l->t('Data source')); ?>" data-placeholder="<?php print($l->t('Data source')); ?>">
			<option value="0"></option>
			<?php foreach($_['user_prov_set'] as $prov){ ?>
			<option value="pr_<?php print($prov['pr_id']); ?>"><?php print($prov['pr_name']); ?></option>
			<?php } ?>
		  </select>
		  <span class="slider-frame" title="<?php print($l->t('Keep directory structure')); ?>">
			<span class="slider-button">flat</span>
		  </span>
		  <input type="checkbox" value="0" class="slider-check" />
		  <span class="urlc" title="<?php print($l->t('URL of the folder to download')); ?>">
			<input id="folderurl" type="text" class="url" value="" placeholder="<?php print($l->t('Folder URL')); ?>" />
		  </span>
		  <span class="load" title="<?php print($l->t('List content of folder')); ?>">
			<button id="loadFolder"><?php print($l->t('List folder')); ?></button>
		  </span>
		  <span class="dling"></span>
		</div>
	  </div>

	  <div id="save_pop">
		<div id="save_list" class="elts folder">
		  <span class="urlc" title="<?php print($l->t('Type name and hit enter to save')); ?>">
			<input id="urllist" type="text" class="url" value="" placeholder="<?php print($l->t('File name')); ?>" />
		  </span>
		  <span class="dling"></span>
		</div>
	  </div>

	  <div id="dialog0" title="<?php print($l->t('Choose file')); ?>">
	  </div>
	  <div id="chosen_file"></div>

	  <?php if(isset($_['curl_error'])){ ?>
	  <div class="personalblock red">
		<?php print($l->t('The application needs the <strong>PHP cURL</strong> extension to be loaded !')); ?>
	  </div>
	  <?php }else{ ?>


	  <div id="dllist"  style="margin-bottom: 20px; min-height: 55px;"> 
		<table id="filestable" class="panel">
		  <thead class="panel-heading">
			<tr>
			  <th class="col-sm-1">
				<a class="columntitle">
				  <span>Protocol</span>
				</a>
			  </th>
			  <th class="col-sm-7">
				<span>File or folder URL</span>
			  </th>
			  <th class="col-sm-1">
			  </th>
			  <th class="col-sm-3">
				<span>Download progress</span>
			  </th>
			</tr>
		  </thead>

		  <tbody id="fileList">
		  <tr id="elt_1" class="elts new">
			<td>
			  <div class="btn-group btn-group-xs ">
				<a class="btn btn-flat btn-default pr-value" href="#" original-title="" data-id="pr_1" style="min-width:42px">HTTP</a>
				<a class="btn btn-flat btn-default dropdown-toggle" data-toggle="dropdown" href="#">
				  <i class="icon-angle-down"></i>
				</a>
				<ul class="dropdown-menu" style="display: none;">
				  <?php foreach($_['user_prov_set'] as $prov){ ?>
				  <li data-id="pr_<?php print($prov['pr_id']); ?>">
				  <a class="action" href="#"><span><?php print($prov['pr_name']); ?></span></a>
				  </li>
				  <?php } ?>
				</ul>
			  </div>

			  <!--			  
			  <select class="chzen-select" title="<?php //print($l->t('Data source')); ?>" data-placeholder="<?php //print($l->t('Data source')); ?>">
				<option value="0"></option>
				<?php //foreach($_['user_prov_set'] as $prov){ ?>
				<option value="pr_<?php //print($prov['pr_id']); ?>"><?php //print($prov['pr_name']); ?></option>
				<?php //} ?>
			  </select>
-->

			</td>
			<td>
			  <span class="urlc" title="<?php print($l->t('enter URL')); ?>">
				<input type="text" class="url" value="" placeholder="<?php print($l->t('enter URL')); ?>" / style="width:100%">
			  </span>
			</td>
			<td>
			  <span class="eltdelete hidden"><i class="icon icon-minus"></i></span>
			  <span class="addelt hidden"><i class="icon icon-plus"></i></span>
			</td>
			<td>  
<div>
  <!--		      <span><a id="getfolderurl" class="btn btn-default btn-flat hidden"><?php print($l->t('List folder')); ?></a></span> -->
			  <span class="dling"></span>
</div>
			</td>
		  </tr>
		  </tbody>
		  <tfoot>
			<tr class="summary text-sm" style="opacity:1">
			  <td colspan=2>
			  </td>
			  <td>
				<span title="<?php print($l->t('Keep directory structure')); ?>" class="slider-frame">
				  <span class="slider-button">flat</span>
				</span>
				<input type="checkbox" value="0" class="slider-check" />
			  </td>
			  <td class="text-right">
				<div id="geturl" class="btn btn-default btn-flat" style="opacity:0.3" ><?php print($l->t('Import all files')); ?></div>
			  </td>
			</tr>
		  </tfoot>
		</table>
	  </div>


	  <table id="hiddentpl" class="hidden">
		<tbody>
		<tr>
		  <td>
			<div class="btn-group btn-group-xs ">
			  <a class="btn btn-flat btn-default pr-value" href="#" original-title="" data-id="pr_1" style="min-width:42px">HTTP</a>
			  <a class="btn btn-flat btn-default dropdown-toggle" data-toggle="dropdown" href="#">
				<i class="icon-angle-down"></i>
			  </a>
			  <ul class="dropdown-menu" style="display: none;">
				<?php foreach($_['user_prov_set'] as $prov){ ?>
				<li data-id="pr_<?php print($prov['pr_id']); ?>">
				<a class="action" href="#"><span><?php print($prov['pr_name']); ?></span></a>
				</li>
				<?php } ?>
			  </ul>
			</div>
		  </td>
		  <td>
			<span class="urlc" title="<?php print($l->t('enter URL')); ?>">
			  <input type="text" class="url" value="" placeholder="<?php print($l->t('enter URL')); ?>" / style="width:100%">
			</span>
		  </td>
		  <td>
			<span class="eltdelete"><i class="icon icon-minus"></i></span>
			<span class="addelt hidden"><i class="icon icon-plus"></i></div>
		  </td>
		  <td>  
			<span class="dling"></span>
		  </td>
		</tr>
		<tbody>
	  </table>

	  <!--
			  <div id="hiddentpl">
			<select class="chzen-select" title="<?php print($l->t('Select data source')); ?>" data-placeholder="<?php print($l->t('Data source')); ?>">
			  <option value="0"></option>
			  <?php foreach($_['user_prov_set'] as $prov){ ?>
			  <option value="pr_<?php print($prov['pr_id']); ?>"><?php print($prov['pr_name']); ?></option>
			  <?php } ?>
			</select>
			<span class="slider-frame" title="<?php print($l->t('Keep directory structure')); ?>">
			  <span class="slider-button">flat</span>
			</span>
			<input type="checkbox" value="0" class="slider-check" />
			<span class="urlc" title="<?php print($l->t('URL of the file to download')); ?>">
			  <input type="text" class="url" value="" placeholder="<?php print($l->t('File URL')); ?>" />
			</span>
			<button class="eltdelete" title="Remove this download">-</button>
			<button class="addelt" title="Add another download">+</button>
			<span class="dling"></span>
		  </div>
	-->	  



	  <?php } ?>


	  <p>



	  <div id="divhisto" style="display:none">
		<?php $status = Array($l->t('Unknown error'),$l->t('OK')); ?>
		
		<div class="text-right" style="margin-bottom:10px">
		  <a id="clearhistory" class="btn btn-default btn-flat" role="button" aria-disabled="false"><?php print($l->t('Clear history')); ?></a>
	    </div>
		
		<table id="histtable" class="panel">
		  <thead class="panel-heading">
			<tr>
			  <th id="fileName" class="column-name col-sm-8">
				<a class="name sort columntitle" style="padding-left:15px;">
				  <span><?php print($l->t('File')); ?></span>
				</a>
			  </th>
			  <th id="headerSize" class="column-date col-sm-2">
				<a class="date columntitle">
				  <span>	<?php print($l->t('Date / Time')); ?></span>
				</a>
			  </th>


			  <th id="headerDate" class="column-mtime col-sm-2">
				<a class="status columntitle">
				  <span><?php print($l->t('Status')); ?></span>
				</a>
			  </th>
			</tr>
		  </thead>



		  <tbody id="filelist">
		  <?php if(!$_['user_history']){ ?>
		  <tr>
			<td colspan="3">
			  <span class="nohist"><?php print($l->t('No history for now')); ?></span>
			</td>
		  </tr>
		  <?php }else{ 
		  foreach($_['user_history'] as $history){ ?>
		  <tr>
			<td class="filename">
			  <div class="filelink-wrap">
				<a class="name" href="#">
				  <span class="fileicon"><i class="icon-file-code"> </i></span>
				  <span class="nametext"><?php print($history['dl_file']); ?></span>
				</a>
			  </div>
			</td>
			<td class="filesize"><?php print($history['dl_ts']); ?></td>
			<td class="date"><?php print($status[$history['dl_status']]); ?></td>
		  </tr>
		  <?php }
		  } ?>
		  </tbody>
		  <tfoot>
		  </tfoot>
		</table>
	  </div>
	  <br />
	  <div id="oc_pw_dialog">
		<label class="nowrap">Master password to decrypt stored provider passwords: </label><input type="password" id="importer_pw" />
	  </div>
	</div>
