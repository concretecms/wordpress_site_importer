<?php  defined('C5_EXECUTE') or die(_("Access Denied."));
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$ph = Loader::helper('form/page_selector');
$form = Loader::helper('form');
$ih = Loader::helper('concrete/interface');
$al = Loader::helper('concrete/asset_library');
$user = Loader::helper('form/user_selector');
	/* something about .post and preventing default.  Maybe for using a standard 'submit' sort of thing but this 2011 and you can't use javascript, go die. */
?>
<script type="text/javascript">

var wpImport = { 
	start_import_not_home: function() {
			if(confirm("<?php echo  t("Seriously? That's where you want your entire blog to go?"); ?>")) 
				wpImport.start_import();
			else
				return false;
	},
	start_import: function() {
		$("#form_box").hide();
		$("#progress .importing").hide();
		$("#progress .parent-page").hide();
		$("#progress").show();
		$("#progress .waiting").show();
		var remain = 0;
		var done = 0;
		$.post('<?php echo  $this->action('import_wordpress_site') ?>', $("#import-wordpress-form").serialize(), function(data) {
			if(data.processed) {
				console.log(data);
				remain = data.remain;
				done = data.processed;
				$("#progress .waiting").hide();
				$("#progress .importing").show();
				$("#progress .bar").progressbar({value: (done / remain) * 100});
				wpImport.add_titles(data.titles);
				wpImport.continue_import(remain,done);
			} else {
				alert ("<?php echo  t("No records to process.") ?>");
			}
		},"json");
	},
	continue_import: function(remain,done) {
		$.post('<?php echo  $this->action('import_wordpress_site') ?>', $("#import-wordpress-form").serialize(), function(data) {
			if(data.remain) {	
				done += data.processed;
				$("#progress .bar").progressbar("option","value", (done / remain) * 100);
				wpImport.add_titles(data.titles);
				wpImport.continue_import(remain,done);
			} else {
				$("#progress h1").html("WORDPRESSED.");
				wpImport.show_link();
				$("#progress .titles").hide("slow");
			}
		},"json");
	},
	show_link: function() {
		$.post('<?php echo  $this->action('get_root_page') ?>', $("#import-wordpress-form").serialize(), function(data) {
			if(data.title) {
				$("#progress .parent-page").html('<?php echo  t("Visit your imported pages: ")?><a href="<?php echo  BASE_URL.DIR_REL ?>'+data.url+'">'+data.title+'</a>');
				$("#progress .parent-page").show("fast");
			}
		},"json");
	},
	add_titles: function(titles) {
		for(var i in titles){
			var title = "<li>"+titles[i]+"</li>";
			$("#progress .titles").prepend(title);
			$("#progress .titles li:gt(9)").remove();
		}
	}
}
	

</script>
<style type="text/css">
</style>
<div class="ccm-dashboard-inner">
   <?php   if (is_array($errors) && count($errors) > 0){ ?>
    <div id="errors" style="height:auto; margin-left:30px;">
        <h2>Errors while Importing</h2>
        <ul>
       <?php   foreach($errors as $e){
            echo "<li>Error - {$e}</li>";
        } ?>
        </ul>
        <p>Note: You will receive errors for file attachment posts. These files are already referenced in posts/pages and are found, so errors here
        are simply to let you know that something other than a page or a post was found in your export.</p>
    </div>
   <?php   } ?>
	<div id="progress">
		<h1 style="background: 0;" class="waiting"><?php echo  t("Waiting for first transaction...")?></h1>
		<h1 style="background: 0;" class="importing"> <?php echo  t("Importing records:"); ?> </h1>
		<div class="bar">
		</div>
		<p class="parent-page">
		</p>
		<ul class="titles" >
		</ul>
	</div>
	<div id="form_box">
    <form id="import-wordpress-form" method="post" action="<?php   echo $this->action('import_wordpress_site') ?>">
    <h2><?php echo  t("Select a page for your WordPress blog to be imported under.")?></h2>
    <div style="width:400px"> 
   <?php   echo FormPageSelectorHelper::selectPage('new-root-wordpress',1); ?>
	</div>
        <h2><?php echo t('Choose a Page Type for WordPress "Pages":')?></h2>
        <?php   echo $form->select('wordpress-pages',$collectiontypes); ?>
        <br /><br/>
        <h2><?php echo t('And one for WordPress "Posts":')?></h2>
        <?php   echo $form->select('wordpress-blogs',$collectiontypes); ?>
        <br />&nbsp;<br/>
        <h2>Import Options</h2>
        <label for="input-images"><?php echo t("Images? ")?></label><input type="checkbox" name="import-images"  />
        
        <div id="import-images-settings">
            <label for="file-set-name"><?php echo  t("New file set for imported images: ") ?></label><input type="text" name="file-set-name" value="" />
        </div>
        <br clear="both" />
        <?php   echo $ih->button(t("Start Import"),'javascript:wpImport.start_import_not_home();','left'); ?>
    </form>
    <br clear="both" />
	 </div>
<script type="text/javascript">
$('document').ready(function(){
    ccm_activateFileSelectors();
	$("#progress").hide();
});
</script>
</div>
