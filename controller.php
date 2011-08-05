<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

class WordpressSiteImporterPackage extends Package {
  protected $pkgDescription = 'Add wordpress import capability to your site';
  protected $pkgName = "WordPress Site Importer";
  protected $pkgHandle = 'wordpress_site_importer';

  protected $appVersionRequired = '5.3.3.1';
  protected $pkgVersion = '1.0';


  public function install() {

	$pkg = parent::install();
    Loader::model('single_page');
    $single_page = SinglePage::add('/dashboard/wordpress_import', $pkg);
    $single_page->update(array('cName' => 'WordPress Import', 'cDescription' => 'Import WordPress Sites'));  

	 $import_stuff = SinglePage::add('/dashboard/wordpress_import/site',$pkg);
  }
}
?>
