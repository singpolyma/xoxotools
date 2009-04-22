<?php

set_time_limit(0);

if(!$_REQUEST['url']) {
   ?>

<h2>Backup your hAtom or XOXO Blog Format Blog</h2>
<form method="get"><div>
   URL: <input type="text" name="url" />
   <input type="submit" value="Go" />
</div></form>

   <?php
   exit;
}//end if ! url

require_once 'OutlineClasses/OutlineFromXOXO.php';
require_once 'OutlineClasses/OutlineFromHATOM.php';
require_once 'xn-app://singpolymaplay/getTidy.php';

$page = getTidy($_REQUEST['url']);
$archives = new OutlineFromXOXO($page,array('classes' => array('archive-list')));

if(!$archives || !$archives->getNode(0)) {
   preg_match('/<div id="ArchiveList">([^\f]*?<\/div>)/',$page,$archives);
   $archives = str_replace('</option>','</a></li>',str_replace('<option value','<li><a href',str_replace('</select>','</ul>',str_replace('<select','<ul',$archives[1]))));
   $archives = new OutlineFromXOXO($archives,array('classes' => array()));
}//end if ! $archives

$urls = array();
foreach($archives->getNodes() as $node) {
   if($node->getField('href#1'))
      $urls[] = $node->getField('href#1');
   else if($node->getField('href'))
      $urls[] = $node->getField('href');
}//end foreach as node

$site = array();
$struct = array();
foreach($urls as $url) {
   $data = getTidy($url);
   $xoxo = new OutlineFromXOXO($data,array('classes' => array('xoxo','posts')));
   $hatom = new OutlineFromHATOM($data,array('resolve' => $_GET['url']));
   $raw = new OutlineFromXOXO($data,array('classes' => array()));

   if(in_array('home',explode(' ',$xoxo->getNode(0)->getField('rel')))) {$site = $xoxo->getNode(0)->toArray();$xoxo->unsetNode(0);$xoxo->reindexNodes();}
   if(in_array('home',explode(' ',$hatom->getNode(0)->getField('rel')))) {$site = $hatom->getNode(0)->toArray();$hatom->unsetNode(0);$hatom->reindexNodes();}
   if(in_array('home',explode(' ',$raw->getNode(0)->getField('rel')))) {$site = $raw->getNode(0)->toArray();$raw->unsetNode(0);$raw->reindexNodes();}

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

   if($xoxo->getNumNodes()) {
      foreach($xoxo->toArray() as $item) {
         if($item['title']) $struct[$item['title']] = $item;
         else $struct[] = $item;
      }//end foreach
   }//end if numnodes
}//end foreach $urls

krsort($struct);

$struct = new Outline($struct);

header('Content-type: application/xhtml+xml; charset=utf-8');

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
echo '<html>'."\n\n";

echo '<head>'."\n";
echo '   <title>Backup';
if($site && $site['text'])
   echo ' of '.str_replace("\n",' ',str_replace("\r",' ',htmlentities($site['text'])));
echo ' - '.htmlentities(date('c',time())).'</title>'."\n";
if($site && $site['href#1'])
   echo '   <link rel="alternate" type="application/xml" title="Posts Feed" href="'.htmlentities($site['href#1']).'" />'."\n";
echo '   <meta http-equiv="Content-Type" content="application/xhtml+xml; charset=utf-8" />'."\n";
echo '</head>'."\n\n";

echo '<body>'."\n\n";

echo '<ul class="xoxo posts hfeed">'."\n\n";

if($site) {
   echo '   <li id="home">'."\n";
   echo '      <a class="feed-title" rel="home bookmark"';
   if($site['href'])
      echo ' href="'.htmlentities($site['href']).'"';
   if($site['title'])
      echo ' title="'.htmlentities($site['title']).'"';
   echo '>'.str_replace("\n",' ',str_replace("\r",' ',htmlentities($site['text']))).'</a>'."\n";
   if(in_array('alternate',explode(' ',$site['rel#1'])))
      echo '      <a rel="alternate" href="'.htmlentities($site['href#1']).'">[site post feed]</a>'."\n";
   echo '   </li>'."\n\n";
}//end if site

foreach($struct->getNodes() as $node) {

   if($node->getField('title'))
      $node->setField('title',(int)((($node->getField('title')/1000000000) < 1000) ? $node->getField('title') : ($node->getField('title')/1000000000)));
   echo '   <li class="hentry"';
   if($node->getField('title'))
      echo ' id="post-'.htmlentities($node->getField('title')).'"';
   echo '>'."\n";
   echo '      <a class="entry-title" rel="bookmark"';
   if($node->getField('href'))
      echo ' href="'.htmlentities($node->getField('href')).'"';
   if($node->getField('title'))
      echo ' title="'.htmlentities($node->getField('title')).'"';
   echo '>'.htmlentities(str_replace("\n",' ',str_replace("\r",' ',$node->getField('text')))).'</a>';
   if($node->getField('title')) {
      if(date('H',$node->getField('title')) || date('i',$node->getField('title')))
         $design_pattern_stamp = date('c',$node->getField('title'));
      else
         $design_pattern_stamp = date('Y-m-d',$node->getField('title'));
   } else $design_pattern_stamp = '';

   $commentsdone = false; $authordone = false; $archivedone = false; $commentsalternatedone = false;
   foreach($node->getFields() as $name => $value) {
      $nameparts = explode('#',$name);
      if(!$nameparts[1] || $nameparts[0] != 'rel') continue;
      $value = explode(' ',$value);

      if(in_array('comments',$value) && !in_array('alternate',$value) && !$commentsdone) {
         $commentsdone = true;
         echo "\n".'      <a rel="comments"';
         if($node->getField('href#'.$nameparts[1])) echo ' href="'.htmlentities($node->getField('href#'.$nameparts[1])).'"';
         echo '>';
         echo (int)$node->getField('text#'.$nameparts[1]);
         echo '</a>';
      }//end if rel=comments

      if(in_array('author',$value) && !$authordone) {
         $authordone = true;
         echo "\n".'      <address class="author vcard">';
         echo "\n".'         <a rel="author"';
         if($node->getField('href#'.$nameparts[1])) echo ' class="url fn" href="'.htmlentities($node->getField('href#'.$nameparts[1])).'"';
         else echo ' class="fn"';
         echo '>';
         echo htmlentities($node->getField('text#'.$nameparts[1]));
         echo '</a>'."\n";
         echo '      </address>';
      }//end if rel=author

    if(in_array('archive',$value) && !$archivedone) {
         $archivedone = true;
         echo "\n".'      <a rel="archive" class="published updated"';
         if($node->getField('href#'.$nameparts[1])) echo ' href="'.htmlentities($node->getField('href#'.$nameparts[1])).'"';
         echo '>';
         echo htmlentities($design_pattern_stamp);
         echo '</a>';
      }//end if rel=archive

    if(in_array('comments',$value) && in_array('alternate',$value) && !$commentsalternatedone) {
         $commentsalternatedone = true;
         echo "\n".'      <a rel="comments alternate"';
         if($node->getField('href#'.$nameparts[1])) echo ' href="'.htmlentities($node->getField('href#'.$nameparts[1])).'"';
         echo '>';
         echo '[post comment feed]';
         echo '</a>';
      }//end if rel=archive

   }//end foreach rel=alternate comments

   if(!$archivedone) echo "\n".'      <a rel="archive" class="published updated">'.htmlentities($design_pattern_stamp).'</a>';

   if($node->getField('body')) {
      echo "\n".'      <dl>'."\n";
      echo '         <dt>body</dt>'."\n";
      echo '            <dd class="entry-content">'.str_replace("\n",' ',str_replace("\r",' ',$node->getField('body')));
      if(!preg_match('/rel="?[^\f]*?tag[^\f]*?"?/',$node->getField('body')) && $node->getField('tags')) {
         echo ' <ul class="tags">';
         $tags = $node->getField('tags');
         $tags = $tags->toArray();
         foreach($tags as $tag) echo ' <li><a rel="tag" href="http://www.technorati.com/tag/'.urlencode($tag['text']).'">'.htmlentities($tag['text']).'</a></li> ';
         echo '</ul> ';
      }//end if ! rel=tag in body
      echo '</dd>'."\n";
      echo '      </dl>';
   }//end if body

   if($node->getNumNodes()) {
      $comments = new Outline($node->getNodes());
      echo "\n".$comments->toXOXO(array('classes' => array('xoxo','comments')));
   }

   echo "\n".'   </li>'."\n\n";
}//end foreach

echo '</ul>'."\n\n";

echo '</body>'."\n\n";
echo '</html>';

?>