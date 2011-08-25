<?php

defined('C5_EXECUTE') or die(_("Access Denied."));

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

		$import_stuff = SinglePage::add('/dashboard/wordpress_import/import', $pkg);
		$import_stuff->update(array('cName' => t('Import')));

		$import_stuff = SinglePage::add('/dashboard/wordpress_import/file', $pkg);
		$import_stuff->update(array('cName' => t('File')));

		$select = AttributeType::getByHandle('select');
          $wpCategory = CollectionAttributeKey::getByHandle('wordpress_category');
          if (!$wpCategory instanceof  CollectionAttributeKey){
               $wpCategory = CollectionAttributeKey::add($select, array('akSelectAllowMultipleValues'=>1,'akSelectOptionDisplayOrder'=>'popularity_desc','akSelectAllowOtherValues'=>1,'akHandle' => 'wordpress_category' , 'akName' => t('Wordpress Category')), $pkg);
          }
          $tags = CollectionAttributeKey::getByHandle('tags');
          if (!$tags instanceof  CollectionAttributeKey){
               $tags = CollectionAttributeKey::add($select, array('akSelectAllowMultipleValues'=>1,'akSelectOptionDisplayOrder'=>'popularity_desc','akSelectAllowOtherValues'=>1,'akHandle' => 'tagsy' , 'akName' => t('Tags')), $pkg);
          }

          $co = new Config();
		$co->setPackageObject($pkg);
		$co->save("WORDPRESS_IMPORT_FID", 0);

	}

	public function uninstall() {
		$db = Loader::db();
		
		$dashPage = Page::getByPath("/dashboard/wordpress_import");
		$dashPage->delete();
		$db->Execute('drop table WordpressItems');
		parent::uninstall();
	}

}

?>
