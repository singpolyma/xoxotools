<?php

require_once 'xn-app://xoxotools/OutlineClasses/Outline.php';
require_once 'xn-app://xoxotools/OutlineClasses/OutlineFromOPML.php';
require_once 'xn-app://xoxotools/OutlineClasses/OutlineFromXML.php';
require_once 'xn-app://xoxotools/OutlineClasses/OutlineFromJSON.php';

   function checkXML($data) {//returns FALSE if $data is well-formed XML, errorcode otherwise
      $rtrn = 0;
      $theParser = xml_parser_create();
      if(!xml_parse_into_struct($theParser,$data,$vals)) {
         $errorcode = xml_get_error_code($theParser);
         if($errorcode != XML_ERROR_NONE && $errorcode != 27)
            $rtrn = $errorcode;
      }//end if ! parse
      xml_parser_free($theParser);
      return $rtrn;
   }//end function checkXML

function std_feed_parse($xmldata) {

   switch(true) {
      case (bool)stristr($xmldata,'<opml'):
         $struct = new OutlineFromOPML($xmldata);
         $inform = 'opml';
         break;
      case (bool)stristr($xmldata,'<rss'):
         $struct = new OutlineFromXML($xmldata,array('rootel' => 'rss','itemel' => 'channel>item','subitemels' => false,'collapsels' => array('title','description')));
         $inform = 'rss20';
         break;
      case (bool)(stristr($xmldata,'<rdf') && stristr($xmldata,'<channel')):
         $struct = new OutlineFromXML($xmldata,array('rootel' => 'rdf:RDF','itemel' => 'item','subitemels' => false,'collapsels' => array('title','description')));
         $inform = 'rss10';
         break;
      case (bool)stristr($xmldata,'<feed'):
         $struct = new OutlineFromXML($xmldata,array('rootel' => 'feed','itemel' => 'entry','subitemels' => false,'collapsels' => array('title','content','summary')));
         $inform = 'atom';
         break;
      case (bool)(stristr($xmldata,'<CHANNEL') && stristr($xmldata,'<ITEM')):
         $struct = new OutlineFromXML($xmldata,array('rootel' => 'CHANNEL','itemel' => 'ITEM','subitemels' => false,'collapsels' => array('TITLE','ABSTRACT')));
         $inform = 'rss10';
         break;
      case (bool)(stristr($xmldata,'<ul') || stristr($xmldata,'<ol') || (!checkXML($xmldata) && stristr($xmldata,'hentry'))):
         $struct = new OutlineFromXML(file_get_contents('http://xoxotools.ning.com/hatom2rss.php?url='.urlencode($_REQUEST['url'])),array('rootel' => 'rss','itemel' => 'channel>item','subitemels' => false,'collapsels' => array('title','description')));
         $inform = 'hatom';
         break;
      case (bool)!checkXML($xmldata):
         $struct = new OutlineFromXML($xmldata,array('subitemels' => false));
         $inform = 'xml';
         break;
      default:
         $struct = new OutlineFromJSON($xmldata,array('itemel' => false));
         $inform = 'json';
   }//end switch TRUE

if(is_a($struct->getField('channel'),'Outline')) {
   $channel = $struct->getField('channel');
   foreach($channel->getFields() as $name => $val)
      $struct->addField($name,$val);
   $struct->unsetField('channel');
}//end if channel

$data = array();
$data['title'] = $struct->getField('title');
if(!$data['title'])
   $data['title'] = $struct->getField('dc:title');
if(!$data['title'])
   $data['title'] = $struct->getField('text');
if(is_a($data['title'],'Outline')) {
   if($data['title']->getNumNodes()) {
      $tmp = $data['title']->getNode(0);
      $data['title'] = $tmp->getField('text');
   } else
      $data['title'] = '';
}//end if is_a title Outline

$data['link'] = $struct->getField('link');
if(is_a($data['link'],'Outline')) {
   $tmp = $data['link'];
   unset($data['link']);
   if($tmp->getField('rel') == 'alternate' || $tmp->getField('type') == 'text/html')
      $data['link'] = $tmp->getField('href');
   if(!$data['link']) {
      foreach($tmp->getNodes() as $node) {
         if($node->getField('rel') == 'alternate' || $node->getField('type') == 'text/html') {
            $data['link'] = $node->getField('href');
            break;
         }//end if rel || type
      }//end foreach nodes
   }//end if ! $data['link']
}//end if link is_a Outline
if(!$data['link'])
   $data['link'] = $struct->getField('id');
if(!$data['link'])
   $data['link'] = $struct->getField('href');

$data['description'] = $struct->getField('description');
if(!$data['description'])
   $data['description'] = $struct->getField('dc:description');
if(!$data['description'])
   $data['description'] = $struct->getField('subtitle');
if(is_a($data['description'],'Outline'))
   $data['description'] = $data['description']->getField('text');
if(!$data['description'])
   $data['description'] = $struct->getField('abstract');

$data['language'] = $struct->getField('language');
if(!$data['language'])
   $data['language'] = $struct->getField('dc:language');

$data['copyright'] = $struct->getField('copyright');
if(!$data['copyright'])
   $data['copyright'] = $struct->getField('dc:rights');

$data['webMaster'] = $struct->getField('webmaster');
if(!$data['webMaster'])
   $data['webMaster'] = $struct->getField('managingeditor');

$data['dc:creator'] = $struct->getField('dc:creator');
if(!$data['dc:creator'])
   $data['dc:creator'] = $struct->getField('dc:contributor');

if($struct->getField('pubdate'))
   $data['pubDate'] = strtotime($struct->getField('pubdate'));
if((!$data['pubDate'] || $data['pubDate'] == -1) && $struct->getField('lastbuilddate'))
   $data['pubDate'] = strtotime($struct->getField('lastbuilddate'));
if((!$data['pubDate'] || $data['pubDate'] == -1) && $struct->getField('dc:date'))
   $data['pubDate'] = strtotime($struct->getField('dc:date'));
if((!$data['pubDate'] || $data['pubDate'] == -1) && $struct->getField('updated'))
   $data['pubDate'] = strtotime($struct->getField('updated'));
if((!$data['pubDate'] || $data['pubDate'] == -1) && $struct->getField('modified'))
   $data['pubDate'] = strtotime($struct->getField('modified'));

$data['category'] = $struct->getField('category');
if(is_a($data['category'],'Outline')) {
   $cats = $data['category'];
   $data['category'] = array();
var_dump($cats->toArray());exit;
   foreach($cats->toArray() as $cat)
      $data['category'][] = $cat['text'];
}//end if is_a Outline
if($data['category'] && !is_array($data['category']))
   $data['category'] = array($data['category']);
if(!$data['category'] && $struct->getField('dc:subject')) {
   $data['category'] = $struct->getField('dc:subject');
   if(is_a($data['category'],'Outline')) {
      $cats = $data['category'];
      $data['category'] = array();
      foreach($cats->toArray() as $cat)
         $data['category'][] = $cat['text'];
   } else {
      $data['category'] = explode(' ',$data['category']);
   }//end if-else $data['category'] is_a Outline
}//end if ! category

$data['image'] = $struct->getField('image');
if(is_a($data['image'],'Outline'))
   $data['image'] = $data['image']->toArray();
if(!$data['image'])
   $data['image'] = $struct->getField('logo');
if(is_a($data['image'],'Outline')) {
   if(!$data['image']->getField('href') && $data['image']->getNumNodes())
      $tmp = $data['image']->getNode(0);
   else
      $tmp = $data['image'];
   $data['image'] = array('url' => $tmp->getField('href'));
}//end if is_a image Outline

$data['items'] = array();

foreach($struct->getNodes() as $node) {
   $item = array();

   $item['title'] = $node->getField('title');
   if(!$item['title'])
      $item['title'] = $node->getField('dc:title');

   $item['link'] = $node->getField('link');
   if(is_a($item['link'],'Outline')) {
      $tmp = $item['link'];
      unset($item['link']);
      $item['link'] = $tmp->getField('href');
      if(!$item['link']) {
         foreach($tmp->getNodes() as $node2) {
            if($node2->getField('rel') == 'alternate' || $node2->getField('type') == 'text/html') {
               $item['link'] = $node2->getField('href');
               break;
            }//end if rel || type
         }//end foreach nodes
      }//end if ! $item['link']
   }//end if link is_a Outline
   if(!$item['link'])
      $item['link'] = $node->getField('href');

   $item['description'] = $node->getField('description');
   if(strlen($node->getField('content:encoded')) > strlen($item['description']))
      $item['description'] = $node->getField('content:encoded');
   if(!$item['description'])
      $item['description'] = $node->getField('dc:description');
   if(!$item['description'])
      $item['description'] = $node->getField('content');
   if(!$item['description'])
      $item['description'] = $node->getField('summary');
   if(!$item['description'])
      $item['description'] = $node->getField('abstract');

   $item['dc:creator'] = $node->getField('dc:creator');
   if(!$item['dc:creator'])
      $item['dc:creator'] = $node->getField('dc:contributor');

   $item['author'] = $node->getField('author');
   if(is_a($item['author'],'Outline')) {
      if(!$item['dc:creator']) $item['dc:creator'] = $item['author']->getField('name');
      $item['author'] = $item['author']->getField('email');
   }//end if author is_a Outline
   if(substr(trim($item['author']),0,19) == 'noemail@noemail.org') {
      $item['author'] = trim($item['author']);
      if(!$item['dc:creator']) {
         $item['dc:creator'] = substr($item['author'],21,strlen($item['author']));
         $item['dc:creator'] = substr($item['dc:creator'],0,strlen($item['dc:creator'])-1);
      }//end if !$item['dc:creator']
      unset($item['author']);
   }//end if noemail@noemail.org

   $item['category'] = $node->getField('category');
   if(is_a($item['category'],'Outline')) {
      $cats = $item['category'];
      $item['category'] = array();
      if(!$cats->getNumNodes())
         $cats = array($cats->toArray());
      else
         $cats = $cats->toArray();
      foreach($cats as $cat) {
         if(!$cat['text']) $cat['text'] = $cat['term'];
         if(!$cat['text']) continue;
         $item['category'][] = $cat['text'];
      }//end foreach cats
   }//end if is_a Outline
   if($item['category'] && !is_array($item['category']))
      $item['category'] = array($item['category']);
   if(!$item['category'] && $node->getField('dc:subject')) {
      $item['category'] = $node->getField('dc:subject');
      if(is_a($item['category'],'Outline')) {
         $cats = $item['category'];
         $item['category'] = array();
         foreach($cats->toArray() as $cat)
            $item['category'][] = $cat['text'];
      } else {
         $item['category'] = explode(' ',$item['category']);
      }//end if-else dc:subject is_a Outline
   }//end if ! category

   $item['comments'] = $node->getField('comments');

   $item['enclosure'] = $node->getField('enclosure');
   if(is_a($item['enclosure'],'Outline')) {
      $tmp = $item['enclosure'];
      $item['enclosure']['url'] = $tmp->getField('url');
      $item['enclosure']['length'] = $tmp->getField('length');
      $item['enclosure']['type'] = $tmp->getField('type');
   }//end if $item['enclosure'] is_a Outline

   $item['guid'] = $node->getField('guid');
   if(is_a($item['guid'],'Outline'))
      $item['guid'] = $item['guid']->getField('text');
   if(!$item['guid'])
      $item['guid'] = $node->getField('id');
   if(!$item['guid'] && $item['link'])
      $item['guid'] = $item['link'];
   if(!$item['guid'])
      $item['guid'] = md5($item['title'].$item['description']);


   $item['pubDate'] = $node->getField('pubdate') ? strtotime($node->getField('pubdate')) : NULL;
   if(!$item['pubDate'])
      $item['pubDate'] = $node->getField('dc:date') ? strtotime($node->getField('dc:date')) : NULL;
   if(!$item['pubDate'])
      $item['pubDate'] = $node->getField('issued') ? strtotime($node->getField('issued')) : 
NULL;
   if(!$item['pubDate'])
      $item['pubDate'] = $node->getField('created') ? strtotime($node->getField('created')) : NULL;
   if(!$item['pubDate'])
      $item['pubDate'] = $node->getField('updated') ? strtotime($node->getField('updated')) : NULL;
   if(!$item['pubDate'])
      $item['pubDate'] = $node->getField('modified') ? strtotime($node->getField('modified')) : NULL;


   $item['source'] = $node->getField('source');
   if(is_a($item['source'],'Outline')) {
      $tmp = $item['source'];
      $item['source'] = array();
      $item['source']['title'] = $tmp->getField('text');
      $item['source']['url'] = $tmp->getField('url');
   }//end if source is_a Outline
   if(!$item['source'] && $node->getField('dc:source'))
      $item['source']['url'] = $node->getField('dc:source');

   $item['wfw:comment'] = $node->getField('wfw:comment');

   $item['wfw:commentRss'] = $node->getField('wfw:commentrss');

   array_push($data['items'],$item);
}//end foreach nodes

$data['items'] = array_values($data['items']);

return $data;

}

?>