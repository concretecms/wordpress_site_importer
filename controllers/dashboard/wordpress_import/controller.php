<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

class DashboardWordpressImportController extends Controller{

	function __construct() {
		$db = Loader::db();
		$records = $db->GetOne("select count(*) from WordpressItems where imported = 0");
		if($records > 0) {
			$this->redirect('/dashboard/wordpress_import/import');
		} else {
			$this->redirect('/dashboard/wordpress_import/file');
		}
	}
	
}
