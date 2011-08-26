<?php  defined('C5_EXECUTE') or die(_("Access Denied."));


class DashboardWordpressImportFileController extends Controller{

	public function view() {
		$db = Loader::db();
		$records = $db->GetOne("select count(*) from WordpressItems where imported = 0");
		$this->set('records',$records);
	}
	
	public function import_wordpress_xml() {
		if($this->post('import-images') == 'on'){
			$this->importImages = true;
			$filesetname;
			($this->post('file-set-name')) ? $this->filesetname = $this->post('file-set-name') : $this->filesetname = t("Imported Wordpress Files") ;
			$this->createFileSet = true;
		}
		$pages = array();

		if(intval($this->post('wp-file')) > 0) {
			Loader::model('file');

               $co = new Config;
               $pkg = Package::getByHandle('wordpress_site_importer');
               $co->setPackageObject($pkg);
               $co->save("WORDPRESS_IMPORT_FID", $this->post('wp-file'));

			$importFile = File::getByID($this->post('wp-file'));
			$nv = $importFile->getVersion();
			$fileUrl =  $nv->getDownloadURL();
			$xml = @simplexml_load_file($fileUrl, "SimpleXMLElement", LIBXML_NOCDATA);

			$items = array();
			foreach($xml->channel->item as $item) {
				$items[] = $item->asxml();
			}
			$db = Loader::db();
			$sql = $db->Prepare('insert into WordpressItems (wpItem) values(?)');

			foreach ($items as $item) {
				$db->Execute($sql,$item);
			}
			$categories = array();
			$namespaces = $xml->getDocNamespaces();
			foreach ( $xml->xpath('/rss/channel/wp:category') as $term_arr ) {
				$t = $term_arr->children( $namespaces['wp'] );
				$categories[] = array(
				'term_id' => (int) $t->term_id,
				'category_nicename' => (string) $t->category_nicename,
				'category_parent' => (string) $t->category_parent,
				'cat_name' => (string) $t->cat_name,
				'category_description' => (string) $t->category_description
				);
			}
			Loader::model('attribute/categories/collection');
			$akt = CollectionAttributeKey::getByHandle("wordpress_category");
			for ($i = 0; $i < count($categories); $i++) {
				$opt = new SelectAttributeTypeOption(0, $categories[$i]['cat_name'], $i);
				$opt = $opt->saveOrCreate($akt);
			}
			foreach ( $xml->xpath('/rss/channel/wp:tag') as $term_arr ) {
			$t = $term_arr->children( $namespaces['wp'] );
               $tags[] = array(
                         'term_id' => (int) $t->term_id,
                         'tag_slug' => (string) $t->tag_slug,
                         'tag_name' => (string) $t->tag_name,
                         'tag_description' => (string) $t->tag_description
                    );
               }

			$akt = CollectionAttributeKey::getByHandle("tags");
			for ($i = 0; $i < count($tags); $i++) {
				$opt = new SelectAttributeTypeOption(0, $tags[$i]['tag_name'], $i);
				$opt = $opt->saveOrCreate($akt);
			}
			
		} else {
			echo t("No file");
			exit;
		}
		$this->view();
	}
}
