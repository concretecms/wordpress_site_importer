<?php  defined('C5_EXECUTE') or die("Access Denied.");
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$ph = Loader::helper('form/page_selector');
$form = Loader::helper('form');
$ih = Loader::helper('concrete/interface');
$al = Loader::helper('concrete/asset_library');
$user = Loader::helper('form/user_selector');
?>
<script type="text/javascript">
/*if there is actually something to import do it and make the button unclickable, otherwise do an alert. Or just hide it until there is something.*/
function ok_submit() {
	$("#fake_submit").hide();
	$("#spinner").show();
	document.forms['import-wordpress-xml'].submit();
}

</script>
<div class="ccm-dashboard-inner">
<form id="import-wordpress-xml" method="post" action="<?php  echo $this->action('import_wordpress_xml') ?>">
 <div style="width:400px"> 
 <h2> <?php echo  t("Please Upload/Select your Wordpress export xml file.") ?></h2>
	  <?php   echo $al->file('wordpress-file','wp-file',t('Choose Wordpress File')); ?>
		<?php  if ($records > 0) {
			?><br/><h2>&nbsp;&nbsp;&nbsp;<?php echo  t("Ready to import ").$records.t(" records.");?></h2>
		<?php }?>
	  <br clear="both"/>
		<?php  /* echo $ih->submit(t('Create Local Data'),'import-wordpress-xml','left');*/ ?>
		<?php  if ($records > 0) {
			$description = t("Choose Import Options");
			$action = 'window.location.href=\''.View::url('/dashboard/wordpress_import/import').'\'';
		} else {
			$description = t("Create Local Data");
			$action = "javascript:ok_submit();";
		}
		echo $ih->button_js($description,$action,'right',null,array('id'=>'fake_submit')); ?>
		<div id="spinner" style="display:none;"> 
			<span style="float:right"><?php echo  t("Building data...") ?>
			<img style="float:right;" src="<?php  echo ASSETS_URL_IMAGES?>/throbber_white_32.gif" />
			</span>
		</div>
	  <br clear="both"/> 
</div>
</form>
</div>

