<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

class WordpressSiteImporterPackage extends Package {
  protected $pkgHandle = 'wordpress_site_importer';
  protected $appVersionRequired = '5.3.3.1';
  protected $pkgVersion = '1.0';
		 public function getPackageDescription() {
			  return t("Add wordpress import capability to your site");
		 }

		 public function getPackageName() {
			  return t("WordPress Site Importer");
		 }


  public function install() {

	$pkg = parent::install();
    Loader::model('single_page');
    $single_page = SinglePage::add('/dashboard/wordpress_import', $pkg);
    $single_page->update(array('cName' => t('WordPress Import'), 'cDescription' => t('Import WordPress Sites')));  

	 $import_stuff = SinglePage::add('/dashboard/wordpress_import/site',$pkg);
  }
}
?>
