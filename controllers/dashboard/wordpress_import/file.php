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

		if($this->post('wp-file') > 0) {
			Loader::model('file');

			$importFile = File::getByID($this->post('wp-file'));
			$nv = $importFile->getVersion();
			$fileUrl =  $nv->getDownloadURL();
			$xml = @simplexml_load_file($fileUrl);

			$items = array();
			foreach($xml->channel->item as $item) {
				$items[] = $item->asxml();
			}
			$db = Loader::db();
			$sql = $db->Prepare('insert into WordpressItems (wpItem) values(?)');

			foreach ($items as $item) {
				$db->Execute($sql,$item);
			}
		} else {
			echo t("No file");
			exit;
		}
		$this->view();
	}
}
