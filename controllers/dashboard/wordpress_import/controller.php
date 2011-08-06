<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

class DashboardWordpressImportController extends Controller{

	function __construct() {
		$this->redirect('/dashboard/wordpress_import/file');
	}
	
}
