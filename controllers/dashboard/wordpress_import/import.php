<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

Loader::model('page_lite','wordpress_site_importer');
//something that would make this truly nice and almost 1:1 with a wordpress site would be to have a page for each "category"
//basically that's the only thing this isn't doing aside from bringing in comments in some form.

class DashboardWordpressImportImportController extends Controller{
	protected $fileset;
	protected $createdFiles = array();
	protected $importImages = false;
	protected $importFiles;
	protected $filesetname = 'Wordpress Files';
	protected $createFileSet;

	function on_start(){
		$cts = array();
		Loader::model('collection_types');
		$list = CollectionType::getList();
		//this just lists out the page_types in concrete5, nothing hard
		foreach($list as $ct){
			$cts[$ct->getCollectionTypeID()] = $ct->getCollectionTypeName();
		}
		$this->set('collectiontypes',$cts);
	}

	public function get_root_pages() {
		Loader::model('page');
		$json = Loader::helper('json');
		$data = array();
		$pageRoot = Page::getByID($this->post('new-root-pages'));
		$data['page-title'] = $pageRoot->getCollectionName();
		$data['page-url'] = $pageRoot->getCollectionPath();
		$postRoot = Page::getByID($this->post('new-root-posts'));
		$data['post-title'] = $postRoot->getCollectionName();
		$data['post-url'] = $postRoot->getCollectionPath();
		echo $json->encode($data);
		exit;
	}

	function import_wordpress_site(){
		$db = Loader::db();
		Loader::library('formatting','wordpress_site_importer');
		$this->importImages = $this->post('import-images');
		$this->createFileSet = $this->importImages;

		$unImported = $db->GetOne("SELECT COUNT(*) FROM WordpressItems where imported = 0");
		if ($unImported > 0) {
			$data = array('remain'=>$unImported,'processed'=>'','titles'=>array());
			
			$xml = $db->GetAll("SELECT wpItem,id FROM WordpressItems where imported = 0 LIMIT 10");
			$data['processed'] = sizeof($xml);
		/*	var_dump($xml);
			exit;
			*/
		} else {
			// sort out by parent ID here
			$pageroot = Page::getByID($this->post('new-root-pages'));
			$postroot = Page::getByID($this->post('new-root-posts'));
			$pages = $db->getAll("SELECT cID, wpID, wpParentID, wpPostType FROM WordpressItems");
			$all_categories = array();
			foreach($pages as $page){
				if (intval($page['wpParentID']) > 0) {
					// have a parent id, figure out where this one goes
					$q  = "SELECT cID FROM WordpressItems WHERE wpID = ?";
					$v = array($page['wpParentID']);
					$parentCID = $db->getOne($q, $v);
					if (intval($parentCID)>0){
						$newParent = Page::getByID($parentCID);
						$rowPage = Page::getByID($page['cID']);
						$rowPage->move($newParent);
					} else {
						$rowPage = Page::getByID($page['cID']);
						if ($page['wpPostType'] == "POST"){
							$rowPage->move($postroot);
						} else {
							$rowPage->move($pageroot);
						}
					}
				} else {
					// no parent, goes off the root
					$rowPage = Page::getByID($page['cID']);
					if ($page['wpPostType'] == "POST"){
						$rowPage->move($postroot);
					} else {
						$rowPage->move($pageroot);
					}
				}

			}
			echo 0;
			exit;
		}

          $co = new Config;
          $pkg = Package::getByHandle('wordpress_site_importer');
          $co->setPackageObject($pkg);
          $importFID = $co->get("WORDPRESS_IMPORT_FID");

          Loader::model('file');
          $importFile = File::getByID($importFID);
		$nv = $importFile->getVersion();
		$fileUrl =  $nv->getDownloadURL();
		$meta_xml = @simplexml_load_file($fileUrl);

          $categories = array();
		$meta_namespaces = $meta_xml->getDocNamespaces();
          foreach ( $meta_xml->xpath('/rss/channel/wp:category') as $term_arr ) {
               $t = $term_arr->children( $meta_namespaces['wp'] );
               $nicename = (string) $t->category_nicename;
               $categories[$nicename] = array(
               'term_id' => (int) $t->term_id,
               'category_nicename' => (string) $t->category_nicename,
               'category_parent' => (string) $t->category_parent,
               'cat_name' => (string) $t->cat_name,
               'category_description' => (string) $t->category_description
               );
          }

          foreach ( $meta_xml->xpath('/rss/channel/wp:tag') as $term_arr ) {
			$t = $term_arr->children( $meta_namespaces['wp'] );
               $slug = (string) $t->tag_slug;
			$tags[$slug] = array(
				'term_id' => (int) $t->term_id,
				'tag_slug' => (string) $t->tag_slug,
				'tag_name' => (string) $t->tag_name,
				'tag_description' => (string) $t->tag_description
			);
		}

		$ids = array();
		foreach($xml as $wpItem){

			libxml_use_internal_errors;
			$item = @new SimpleXMLElement($wpItem['wpItem']);
			$p = new PageLite();

			//Use that namespace
			$title = (string)$item->title;
			$datePublic = $item->pubDate;
			$namespaces = $item->getNameSpaces(true);
			//Now we don't have the URL hard-coded

			$wp = $item->children($namespaces['wp']);
			if ($wp->status == "auto-draft"){
				continue;
			}
			$wpPostID = (int)$wp->post_id;
			$content = $item->children($namespaces['content']);
			$content = wpautop((string)$content->encoded);
			/*
				find the caption in the caption.
				replace [caption ... with <div class="wp-caption-frame"> //alternatively there should be a custom image template that creates a caption.
				that might be the more c5 way to do this.
				then [/caption] with the image and then <span class="wp-caption-text">$caption_text</span></div>
				???
			*/
			$comments = $wp->comment;
			$all_comments = array();
			if (count($comments)>0){
				if (intval($comments->comment_id)>0) {
					// single comment
					$comment_details = array();
					$comment_details['commentText'] = (string) $comments->comment_content;
					$comment_details['name'] = (string) $comments->comment_author;
					$comment_details['email'] = (string) $comments->comment_author_email;
					$comment_details['approved'] = (int) $comments->comment_approved;
					$comment_details['comment_type'] = (string) $comments->comment_type;
					$comment_details['comment_date'] = (string) $comments->comment_date;
					$all_comments[] = $comment_details;
				} else {
					// we have multiple comments for this post, loop over them in turn.

					foreach($comments as $comment){
						$comment_details = array();
						$comment_details['commentText'] = (string) $comment->comment_content;
						$comment_details['name'] = (string) $comment->comment_author;
						$comment_details['email'] = (string) $comment->comment_author_email;
						$comment_details['approved'] = (int) $comment->comment_approved;
						$comment_details['comment_type'] = (string) $comments->comment_type;
						$comment_details['comment_date'] = (string) $comments->comment_date;
						$all_comments[] = $comment_details;
					}
				}
			}
			$excerpt = $item->children($namespaces['excerpt']);
			$postDate = (string)$wp->post_date;
			$postType = (string)$wp->post_type;
			$dc = $item->children($namespaces['dc']);
			$author = (string)$dc->creator;
			$parentID = (int)$wp->post_parent;

               $out_tags = array();
               $out_categories = array();

               foreach ( $item->category as $c ) {
				$att = $c->attributes();
				if ( isset( $att['nicename'] ) ){
                         $nicename = (string) $att['nicename'];
                         if ($att['domain']== "category"){
                              $realname = $categories[$nicename]['cat_name'];
                              $out_categories[] = $realname;
                         } else {
                              $realname = $tags[$nicename]['tag_name'];
                              $out_tags[] = $realname;
                         }
                    }
			}

			$p->setTitle($title);
			$p->setContent($content);
			$p->setAuthor($author);
			$p->setWpParentID($parentID);
			$p->setPostDate($postDate);
			$p->setPostType($postType);
			$p->setCategory($out_categories);
               $p->setTags($out_tags);
			$p->setPostID($wpPostID);
			$p->setExcerpt($excerpt);
			$p->setComments($all_comments);

			//so we just throw these guys in an array
			$pages[$wpItem['id']] = $p; //postID is unique
			$ids[] = $wpItem['id'];
			$data['titles'][] = $title;
			
		}
		//call the function below
		$this->buildSiteFromWordPress($pages);

		$db->Execute('UPDATE WordpressItems set imported=1 where id in('.implode(',',$ids).')');
		$json = Loader::helper('json');
		echo $json->encode($data);
		exit;

		//foreach ($xml->id as $id)
		//idarray
		//delete or set imported
		
	}
	
	
	function buildSiteFromWordPress(array $pages){
		Loader::model('page');
		//this creates the fileset and sets it as a protected property of this controller class so we can reference it without throwing these defines around, i'll get rid of em
		//eventually
		if($this->createFileSet){
			Loader::model('file_set');
			$fs = new FileSet();
			$u = new User();
			$uID = User::getUserID();
			$newFs = FileSet::createAndGetSet($this->filesetname, 1,$uID);
			$this->fileset = $newFs;
		}



		$errors = array();
		//$message = '';
		//get our root page
		$pageroot = Page::getByID($this->post('new-root-pages'));
		$postroot = Page::getByID($this->post('new-root-posts'));
		//this is how / where to set another page for page-type pages.

		//ok so basically our keys in our array are wordpress postIDs, which are pages in the system
		//so what we need to do now (thinking here) is that we need to arrange these posts into a tree
		//$pages is in the format of the postID => pageLiteObject
		Loader::model('collection_types');
		$ctPagesID = CollectionType::getByID($this->post('wordpress-pages'));
		$ctBlogID = CollectionType::getByID($this->post('wordpress-blogs'));
		//we want to reference the collection type we are adding based on either a post or a page
		$collectionTypesForWordpress = array("POST"=>$ctBlogID,"PAGE"=>$ctPagesID);

		$parentIDPageLiteRel = array();
		$createdPages = array();
		$createdPagesReal = array();

		$fakeCreatedPages = array();
		//so our homepage is zero, and we need that in our created page, even though it isn't a page that is created for association issues but it absolutely has to be 0.
		//Then it is a relational mapping issue, this puppy took a bit of thought
		//
		$createdPagesReal[0] = $pageroot;
		//so foreach pages
		foreach($pages as $xmlID => $pageLite){
			$ct = $collectionTypesForWordpress[$pageLite->getPostType()];


			//create the pages
			//right now i am only handling posts and pages, we have to ignore attachments as they are posted elsewhere or referenced in posts or pages
			if(is_a($ct,CollectionType)){
				if ($pageLite->getPostType() == "POST"){
					$createdPagesReal[$pageLite->getPostID()] =  $this->addWordpressPage($postroot, $ct, $pageLite, $xmlID);
				} else {
					$createdPagesReal[$pageLite->getPostID()] =  $this->addWordpressPage($pageroot, $ct, $pageLite, $xmlID);
				}
				//here's how we map our pages to pages
				
			}else{
				//this is kind of spooky and frustrating to see.
				$errors[] = t("Un-supported post type for post - ").$pageLite->getTitle();
			}
		}
		
		$this->set('message',t('Wordpress export pages imported under ').$pageroot->getCollectionName().".<br /> ". t('Wordpress export posts imported under ').$postroot->getCollectionName().".");
		$this->set('errors',$errors);
	}
	
	
	// this function takes a page as an arguement, the collection type and a page-lite object.
	function addWordpressPage(Page $p, CollectionType $ct, PageLite $pl, $xmlID){
/*		echo $pl->getPostdate();
		exit;
		*/
		$u = new User();
		$pageAuthor = $pl->getAuthor();
		$ui = UserInfo::getByUserName($pageAuthor);

		if (is_object($ui)){
			$uID = $ui->getUserID();
		} else {
			$uID = $u->getUserID();
		}


		$pageData = array('cName' => $pl->getTitle(),'cDatePublic'=>$pl->getPostdate(),'cDateAdded'=>$pl->getPostdate(),'cDescription' => $pl->getExcerpt(), 'uID'=> $uID);
		$newPage = $p->add($ct,$pageData);

          if (is_array($pl->getCategory())){
              $newPage->setAttribute('wordpress_category', $pl->getCategory());
          }
          if (is_array($pl->getTags())){
              $newPage->setAttribute('tags', $pl->getTags());
          }
		if (count($pl->getComments())>0){
			$blocks = $newPage->getBlocks("Blog Post Footer");
			$haveGuestbook = 0;
			foreach($blocks as $block){
				if ($block->getBlockTypeHandle() == "guestbook"){
					$bID = $block->getBlockID();
					$haveGuestbook = 1;
				}
			}
			if ($haveGuestbook == 0){
				$data = array();
				$data['title'] = "Comments:";
				$data['dateFormat'] = "M jS, Y";
				$data['requireApproval'] = 0;
				$data['displayGuestBookForm'] = 1;
				$data['authenticationRequired'] = 0;
				$data['displayCaptcha'] = 1;
				$bt = BlockType::getByHandle("guestbook");
				$guestbook = $newPage->addBlock($bt, "Blog Post Footer", $data);
				$bID = $guestbook->getBlockID();
			}
			Loader::model('comment_lite','wordpress_site_importer');
			$comments = $pl->getComments();
			foreach ($comments as $comment){
				if (!$comment['comment_type'] == "pingback"){
					CommentLite::addcomment($bID, $comment['commentText'], $comment['name'], $comment['email'], $comment['approved'], $newPage->getCollectionID(), 0, $comment['comment_date']);
				}
			}
		}
		$blocks = $newPage->getBlocks("Main");
		foreach($blocks as $block){
			$block->delete();
		}
		$blocks = $newPage->getBlocks("Blog Post More");
		foreach($blocks as $block){
			$block->delete();
		}
		Loader::model('block_types');
		$bt = BlockType::getByHandle('content');
		$data = array();

		$data['content'] = ($this->importImages) ? $this->determineImportableMediaFromContent($pl->getContent(),$pageData) : $pl->getContent(); //we're either importing images or not
		$newPage->addBlock($bt, "Main", $data);

		$db = Loader::db();
		$q = 'UPDATE WordpressItems SET cID = ?, wpID = ?, wpParentID = ?, wpPostType = ? WHERE id=?';
		$v = array($newPage->getCollectionID(), $pl->getPostID(), $pl->getWpParentID(),$pl->getPostType(),$xmlID);
		$res = $db->query($q, $v);
		return $newPage;
	}

	
	function determineImportableMediaFromContent($content,$pageData){
		/*
		After looking at how wordpress actually does this I was completely wrong.  This is actually working ok but could probably use some sprucing up.
		*/
		
		//TODO: continually revisit this regex;
		$pattern = '/<a href="([^"]*)"><img.*(?:title="([^"]*)")? src="([^"]*)".*\/><\/a>/';
		$matches = array();
		if(preg_match_all($pattern,$content,$matches)){
			Loader::library('wordpress_file_post_importer','wordpress_site_importer');
			Loader::model('file');
			//get how many potential file matches we have here
			//match all fills an array so we iternate node 0 which is the match then get use that node as a key to access the rest
			$count = 0;
			$matchedFiles = array();
			foreach($matches[0] as $key => $value){
				$matchesFiles[$key] = array('thumb' => $matches[3][$key], 'main'=> $matches[1][$key],'fullMatch'=>$matches[0][$key]);
				//print_r matches if you need to see how it works
			}
			foreach($matchesFiles as $mfers){  //at this point the variable name made sense
				$tBase = basename($mfers['thumb']);
				$mBase  = basename($mfers['main']);

				if(array_key_exists($tBase,$this->createdFiles) && is_a($this->createdFiles[$tBase],'FileVersion')){
					$thumbFile = $this->createdFiles[$tBase];
				}else{
					$thumbFile = WordpressFileImporter::importFile($mfers['thumb']);
					$this->createdFiles[$tBase] = $thumbFile;
				}
				if(array_key_exists($mBase,$this->createdFiles) && is_a($this->createdFiles[$mBase],'FileVersion')){
					$fullFile = $this->createdFiles[$mBase];
				}else{
					$fullFile = WordpressFileImporter::importFile($mfers['main']);
					$this->createdFiles[$mBase] = $fullFile;
				}
				if($thumbFile instanceof FileVersion && $fullFile instanceof FileVersion){
					$this->fileset->addFileToSet($thumbFile);
					$this->fileset->addFileToSet($fullFile);
					$thumbID = $thumbFile->getFileID();
					$mainID = $fullFile->getFileID();
					$replacement = '<a href="{CCM:FID_'.$mainID.'}"><img src="{CCM:FID_'.$thumbID.'}" alt="'.$fullFile->getTitle().'" title="'.$fullFile->getTitle().'" /></a>';
					//replace the matched one with what we want.
					$content = str_replace($mfers['fullMatch'],$replacement,$content);
				}
			}
		}

		return $content;
	}
}
?>
