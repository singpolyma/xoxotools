<?php

function feed2hatom($url) {

XN_Application::includeFile('alphacomponents','/Alpha/Format/RSS.php');

$feed = XNC_Alpha_Format_RSS::load($url);
$outlinearray = array();
$outlinearray[0]['rel'] = 'home';
$outlinearray[0]['text'] = $feed->title;
$outlinearray[0]['href'] = $feed->link;
$outlinearray[0]['title'] = $feed->description;
$outlinearray[0]['text#1'] = '[feed]';
$outlinearray[0]['href#1'] = $_GET['url'];
$count = 1;
if(!$feed->items) return $outlinearray;;
foreach($feed->items as $item) {
   $outlinearray[$count]['text'] = $item->title;
   $outlinearray[$count]['href'] = $item->link;
   if($item->description) {
      $outlinearray[$count]['body'] = str_replace('</br>','',str_replace('<br>','<br />',$item->description));
      $outlinearray[$count]['body>>class'] = 'entry-content';
   }//end if description
   if($item->pubDate) {
      $outlinearray[$count]['title'] = $item->pubDate*1000000000;
      $outlinearray[$count]['published'] = date('c',$item->pubDate);
      $outlinearray[$count]['published>>class'] = 'published';
   }//end if pubDate
   if($item->comments) {
      $outlinearray[$count]['href#1'] = $item->comments;
      $outlinearray[$count]['text#1'] = 'comments';
      $outlinearray[$count]['rel#1'] = 'comments';
   }//end if comments
   if($item->author || $item->dc->creator) {
      $outlinearray[$count]['text#2'] = $item->author ? $item->author : $item->dc->creator;
      $outlinearray[$count]['rel#2'] = 'author';
      $outlinearray[$count]['class#2'] = 'author vcard fn';
   }//end if comments
   $outlinearray[$count]['rel'] = 'bookmark';
   $outlinearray[$count]['class'] = 'entry-title';
   $count++;
}//end foreach feed->items

return $outlinearray;

}//end function feed2hatom

?>