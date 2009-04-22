<?php

if(!$_GET['url']) {
   ?>
   Enter a URL to an <a href="http://blogxoxo.blogspot.com/2006/01/xoxo-blog-format.html">XOXO Blog Format</a> or <a href="http://microformats.org/wiki/hatom">hAtom</a> blog to get an RSS feed for that blog.
   <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>"><div>
      <input type="hidden" name="xn_auth" value="no" />
      URL: <input type="text" name="url" value="" />
      <input type="submit" value="Go" />
   </div></form>
   <?php
   exit;
}//end if ! url

require_once 'OutlineClasses/OutlineFromXOXO.php';
require_once 'OutlineClasses/OutlineFromHATOM.php';
require_once 'xn-app://singpolymaplay/getTidy.php';

$data = getTidy($_GET['url']);
preg_match('/<body>[^\f]*?<\/body>/',$data,$body);
if($body[0]) $data = $body[0];

//$data = preg_replace('/<(img|meta|link|hr|br)([^<>]*?)([\/]?)>/i','<$1$2 />', $data);
//$data = preg_replace('/&([^;]{10})/i','&amp;$1', $data);
//$data = str_replace('<HEAD>','<head>',$data);
//$data = str_replace('</HEAD>','</head>',$data);

   $xoxo = new OutlineFromXOXO($data,array('classes' => array('xoxo','posts')));
   $hatom = new OutlineFromHATOM($data,array('resolve' => $_GET['url']));
   $raw = new OutlineFromXOXO($data,array('classes' => array()));

   $site = false;
   if($xoxo->getNumNodes() && in_array('home',explode(' ',$xoxo->getNode(0)->getField('rel')))) {$site = $xoxo->getNode(0)->toArray();$xoxo->unsetNode(0);$xoxo->reindexNodes();}
   if($hatom->getNumNodes() && in_array('home',explode(' ',$hatom->getNode(0)->getField('rel')))) {$site = $hatom->getNode(0)->toArray();$hatom->unsetNode(0);$hatom->reindexNodes();}
   if($raw->getNumNodes() && in_array('home',explode(' ',$raw->getNode(0)->getField('rel')))) {$site = $raw->getNode(0)->toArray();$raw->unsetNode(0);$raw->reindexNodes();}

   if($xoxo->getNumNodes() && $hatom->getNumNodes()) {//fill in XOXO with hAtom
      for($i = 0; $i < $xoxo->getNumNodes(); $i++) {
         $node = $xoxo->getNode($i);
         foreach($node->getFields() as $name => $value)
            $hatom->_subnodes[$i]->setField($name,$value);
      }//end for
      $xoxo = $hatom;
   } else if($xoxo->getNumNodes()) {
   } else if($hatom->getNumNodes()) {   
      $xoxo = $hatom;
   } else {
      $xoxo = $raw;
   }//end if-elses

$struct = $xoxo->toArray();

$guids = array();

header('Content-Type: application/xml;charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
echo '<rss version="2.0" '."\n";
echo '      xmlns:content="http://purl.org/rss/1.0/modules/content/"'."\n";
echo '      xmlns:wfw="http://wellformedweb.org/CommentAPI/"'."\n";
echo '      xmlns:dc="http://purl.org/dc/elements/1.1/"'."\n";
echo '      xmlns:slash="http://purl.org/rss/1.0/modules/slash/"'."\n";
echo '>'."\n";
echo '<channel>'."\n";

if($site) {//if metadata
   $site['href'] = $site['href'] ? $site['href'] : $_GET['url'];
   echo '   <title>'.htmlspecialchars($site['text']).'</title>'."\n";
   echo '   <link>'.htmlspecialchars($site['href']).'</link>'."\n";
   echo '   <description>'.htmlspecialchars($site['title']).'</description>'."\n";
} else {
   $theParser = xml_parser_create();
   xml_parse_into_struct($theParser,$data,$vals);
   xml_parser_free($theParser);
   $pagetitle = '';
   foreach($vals as $el) {
      if($el['tag'] = 'TITLE' && trim($el['value'])) {$pagetitle = trim($el['value']);break;}
   }//end foreach
   unset($vals);
   if(!$pagetitle) {$pagetitle = $_GET['url'];}
   echo '   <title>'.htmlspecialchars($pagetitle).'</title>'."\n";
   echo '   <link>'.htmlspecialchars($_GET['url']).'</link>'."\n";
}//end if-else rel==home

$domain = explode('/',$_REQUEST['url']);
$domain = 'http://'.$domain[2];

foreach($struct as $item) {
   if(!is_array($item)) continue;
   if($item['href'] && $item['href']{0} == '/') {$item['href'] = $domain.$item['href'];}
   $time = $item['title'];
   if($time) {
      $time = (($time/1000000000) < 1000) ? $time : ($time/1000000000);
      $time = date('r',$time);
   }//end if time
   if(!$item['body'] && $item['summary']) $item['body'] = $item['summary'];
   $tags = array();
   if($item['body']) {
      $theParser = xml_parser_create();
      xml_parse_into_struct($theParser,'<body>'.$item['body'].'</body>',$vals);
      xml_parser_free($theParser);
      foreach($vals as $el) {
         if(in_array('tag',explode(' ',$el['attributes']['REL']))) {$tags[] = $el['value'];}
      }//end foreach
      unset($vals);
   }//end if body
   foreach($item as $id => $field) {
      $id = explode('#',$id);
      if(!$id[1] || $id[0] != 'rel') {continue;}
      $field = explode(' ',$field);
      if(in_array('comments',$field) && !in_array('alternate',$field)) {
         $item['commenturl'] = $item['href#'.$id[1]];
         if($item['commenturl'] && is_numeric($item['text#'.$id[1]])) {$item['commentnum'] = (int)$item['text#'.$id[1]];}
      }//end if comments
      if(in_array('author',$field)) {
         $item['author'] = $item['text#'.$id[1]];
      }//end if comments
      if(in_array('comments',$field) && in_array('alternate',$field)) {
         $item['commentfeed'] = $item['href#'.$id[1]];
      }//end if comments
   }//end foreach
   echo "\n";
   echo '   <item>'."\n";
   if($item['text']) {echo '      <title>'.htmlspecialchars(str_replace('  ',' ',str_replace("\r",' ',str_replace("\n",' ',trim(html_entity_decode($item['text'])))))).'</title>'."\n";}
   if($item['href']) {echo '      <link>'.htmlspecialchars($item['href']).'</link>'."\n";}
   if($item['href'] && !in_array($item['href'],$guids)) {
      echo '      <guid>'.htmlspecialchars($item['href']).'</guid>'."\n";
      $guids[] = $item['href'];
   } else {
      echo '      <guid isPermaLink="false">'.md5($item['text'].$item['body'].$time).'</guid>'."\n";
   }//end if-else guid
   if($time) {echo '      <pubDate>'.$time.'</pubDate>'."\n";}
   if($item['author']) {echo '      <dc:creator>'.htmlspecialchars(strip_tags($item['author'])).'</dc:creator>'."\n";}
   if($tags && count($tags)) {
      foreach($tags as $tag) {
         echo '      <category>'.htmlspecialchars($tag).'</category>'."\n";
      }//end foreach
   }//end if tags
   if($item['commenturl']) {echo '      <comments>'.htmlspecialchars($item['commenturl']).'</comments>'."\n";}
   if($item['commentnum'] || $item['commentnum'] === 0 || $item['commentnum'] === '0') {echo '      <slash:comments>'.(int)$item['commentnum'].'</slash:comments>'."\n";}
   if($item['commentfeed']) {echo '      <wfw:commentRss>'.htmlspecialchars($item['commentfeed']).'</wfw:commentRss>'."\n";}
   if($item['body']) {echo '      <description>'.htmlspecialchars($item['body']).'</description>'."\n";}
   echo '   </item>'."\n";
}//end foreach

echo '</channel>'."\n";
echo '</rss>';

?>