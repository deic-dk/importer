
<fieldset id="importerSettings" class="section">

  <h2><?php p($l->t('Importer'));?></h2>

  <?php foreach ($_['user_prov_set'] as $prov){ ?>

    <p><input class="importer_provider" type="checkbox" id="<?php print($prov['pr_name']) ?>" name="<?php print($prov['pr_name']) ?>" <?php p((($prov['pr_active'] == 1) ? 'checked="checked"' : '')); ?>/> <label><?php p($l->t('Enable'));?> <?php  print($prov['pr_name']) ?></label></p>

  <?php } ?>
	
</fieldset>

