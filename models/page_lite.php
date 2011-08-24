<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

class PageLite extends Object{
	protected $title;
	protected $content;
	protected $wpParentID;
	protected $comments = array();
	protected $author;
	protected $postType;
	protected $category;
     protected $tags;
	protected $postDate;
	protected $wpPostID; //needed to relate
	function setPropertiesFromArray($array){
		//TODO: maybe put some preg_replace here?
		parent::setPropertiesFromArray($array);
	}
	function setTitle($title){
		$this->title = $title;
	}
	function getTitle(){
		return $this->title;
	}
	function setContent($content){
		//set up wordpress
		$this->content = $content;
	}
	function getContent(){
		return $this->content;
	}
	function setWpParentID($id){
		$this->wpParentID = $id;
	}
	function getWpParentID(){
		return $this->wpParentID;
	}
	function setComment($comment){
		$this->comments[] = $comment;
	}
	function getComment(){
		return $this->comments;
	}
	function setAuthor($author){
		$this->author = $author;
	}
	function getAuthor(){
		return $this->author;
	}
	function setPostType($type){
		$this->postType = strtoupper($type);
	}
	function getPostType(){
		return $this->postType;
	}
	function setCategory($category){
		$this->category = $category;
	}
	function getCategory(){
		return $this->category;
	}
     function setTags($tags){
		$this->tags = $tags;
	}
	function getTags(){
		return $this->tags;
	}
	function setPostDate($date){
		$this->postDate = $date;
	}
	function getPostdate(){
		return date('y-m-d H:i:s ',strtotime($this->postDate));
	}
	function setPostID($id){
		$this->wpPostID = $id;
	}
	function getPostID(){
		return $this->wpPostID;
	}
	function setExcerpt($ex){
		$this->wpExcerpt = $ex;
	}
	function getExcerpt(){
		return $this->wpExcerpt;
	}
}
