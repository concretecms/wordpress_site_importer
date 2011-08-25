<?php defined('C5_EXECUTE') or die("Access Denied.");

class CommentLite {

public function addcomment($bID, $commentText, $name, $email, $approved, $cID, $uID = 0, $timestamp = null) {
  self::addEntry($bID, $commentText, $name, $email, $approved, $cID, $uID, $timestamp);
}

public static function addEntry($bID, $commentText, $name, $email, $approved, $cID, $uID = 0, $timestamp = null) {
  $txt = Loader::helper('text');

  $db = Loader::db();
  $query = "INSERT INTO btGuestBookEntries (bID, cID, uID, user_name, user_email, commentText, approved, entryDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
  $v = array($bID, $cID, intval($uID), $txt->sanitize($name), $txt->sanitize($email), $txt->sanitize($commentText), $approved, $timestamp);
  $res = $db->query($query, $v);

  $number = 1;//stupid cache stuff
  $ca   = new Cache();
  $db   = Loader::db();
  $count = $ca->get('GuestBookCount',$cID."-".$bID);
  if($count && $number){
    $count += $number;
  } else{
    $q = 'SELECT count(bID) as count
    FROM btGuestBookEntries
    WHERE bID = ?
    AND cID = ?
    AND approved=1';
    $v = array($bID, $cID);
    $rs = $db->query($q,$v);
    $row = $rs->FetchRow();
    $count = $row['count'];
  }
  $ca->set('GuestBookCount',$cID."-".$bID,$count);
}

}