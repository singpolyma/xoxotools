<?php

header('Content-type: text/html;charset=utf-8');

if(!$_REQUEST['url']) {
   header('Content-type: text/plain');
   header('Location: http://xoxotools.ning.com/proxy/',true,302);
   exit;
}//end if ! url

require('normalize_url.php');

$_REQUEST['url'] = normalize_url(trim($_REQUEST['url']));
$domain = explode('/',$_REQUEST['url']);
$domain = strtolower(str_replace('*','',$domain[2]));
$domain = explode('.',$domain);
if(count($domain) > 2)
   unset($domain[0]);
$domain = implode('.',$domain);

$related = XN_Query::create('Content')
         ->filter('owner','=')
         ->filter('type','eic','PageSetup')
         ->filter('description','eic',$domain);
$related = $related->execute();

$globs = array();
$unique = array();
$itself = array();

foreach($related as $item) {
   if(strstr($item->title,'*'))
      $globs[$item->title] = unserialize($item->my->microformats);
   else
      $unique[$item->title] = unserialize($item->my->microformats);
}//end foreach related

if(in_array($_REQUEST['url'],array_keys($unique))) {
   $itself = $unique[$_REQUEST['url']];
} else {
   foreach(array_keys($globs) as $glob) {
      if(preg_match('/'.str_replace('\*','.*',preg_quote($glob,'/')).'/',$_REQUEST['url'])) {
         $itself = $globs[$glob];
         break;
      }//end if preg_match
   }//end foreach globs
}//end if-else in_array unique

require_once 'xn-app://singpolymaplay/getTidy.php';

$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
$doc->loadHTML(getTidy($_REQUEST['url']));

require_once 'to_hcard.php';

echo '<div style="padding:20px;">'."\n";

//keep existing hCards
$xpath = new DOMXPath($doc);
$results = $xpath->query("//*[contains(@class,'vcard')]");
foreach($results as $node) {
   $newDom = new DOMDocument;
   $newDom->appendChild($newDom->importNode($node,1));
   echo '   '.str_replace("<?xml version=\"1.0\"?>\n",'',$newDom->saveXML());
}//end foreach results as node

//keep existing XOXO
$xpath = new DOMXPath($doc);
$results = $xpath->query('//ul | //ol');
foreach($results as $node) {
   $newDom = new DOMDocument;
   $newDom->appendChild($newDom->importNode($node,1));
   echo '   '.str_replace("<?xml version=\"1.0\"?>\n",'',$newDom->saveXML());
}//end foreach results as node

$urlel = $doc->createElement('url',$_REQUEST['url']);
$urlel->setAttribute('href',$_REQUEST['url']);
$doc->appendChild($urlel);

foreach($itself as $uf) {
   switch($uf['microformat']) {
      case 'hCard': to_hcard($uf,$doc);break;
   }//end switch
}//end foreach itself

echo '</div>';

?>