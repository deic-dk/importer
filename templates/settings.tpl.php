
<fieldset id="dlSettings" class="personalblock">

  <legend><strong><?php p($l->t('Downloader'));?></strong></legend>

  <?php foreach ($_['user_prov_set'] as $prov){ ?>
      
    <p><input class="ocdownloader_provider" type="checkbox" id="<?php print($prov['pr_name']) ?>" name="<?php print($prov['pr_name']) ?>" <?php p((($prov['pr_active'] == 1) ? 'checked="checked"' : '')); ?>> <label>Enable <?php  print($prov['pr_name']) ?></label></p>
	
  <?php } ?>
	
</fieldset>

