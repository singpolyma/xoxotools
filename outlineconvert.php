<?php

require_once 'OutlineClasses/Outline.php';
require_once 'OutlineClasses/OutlineFromXOXO.php';
require_once 'OutlineClasses/OutlineFromOPML.php';
require_once 'OutlineClasses/OutlineFromXML.php';
require_once 'OutlineClasses/OutlineFromJSON.php';

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

if(!($_REQUEST['url'] || $_REQUEST['data'])) {//if we have no data to convert, output the form
   ?>
<div style="float:right;">
<b>Input Formats Supported:</b>
<ul>
   <li>XOXO</li>
   <li>OPML</li>
   <li>RSS 1.0</li>
   <li>RSS 2.0</li>
   <li>ATOM</li>
   <li>JSON (raw)</li>
   <li>Arbitrary XML</li>
</ul>
</div>
<b>From URL:</b>
<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>"><div>
   Output Format: <select name="output">
      <option value="xoxo">XOXO</option>
      <option value="opml">OPML</option>
      <option value="json">JSON</option>
   </select><br />
   Classes (optional): <input type="text" name="classes" value="" /><br />
   Simplify? <input type="checkbox" name="simplify" /><br />
   Ensure HTML <i>and</i> XML URLs? <input type="checkbox" name="urlfill" /><br />
   Enter a URL: <input type="text" name="url" value="" /><br />
   <input type="submit" name="submit" value="Convert" />
</div></form>
<br /><br />
<b>From Data:</b>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>"><div>
   Output Format: <select name="output">
      <option value="xoxo">XOXO</option>
      <option value="opml">OPML</option>
      <option value="json">JSON</option>
   </select><br />
   Classes (optional): <input type="text" name="classes" value="" /><br />
   Simplify? <input type="checkbox" name="simplify" /><br />
   Ensure HTML <i>and</i> XML URLs? <input type="checkbox" name="urlfill" /><br />
   <textarea rows="15" cols="60" name="data"></textarea><br />
   <input type="submit" name="submit" value="Convert" />
</div></form>

   <?php
   exit;
}//end if !url||data

if($_REQUEST['url'])//if passed a URL, fetch the URL and make it's contents == data
   $_REQUEST['data'] = file_get_contents($_REQUEST['url']);

if($_REQUEST['classes']) {$classes = explode(' ',$_REQUEST['classes']);}else{$classes = array();}//if we were passed classes, explode via space into array, otherwise classes is an empty array

switch(TRUE) {//determine input format
   case stristr($_REQUEST['data'],'<opml'):
      $struct = new OutlineFromOPML($_REQUEST['data']);break;
   case (stristr($_REQUEST['data'],'<ul') || stristr($_REQUEST['data'],'<ol')):
      $struct = new OutlineFromXOXO($_REQUEST['data'],array('classes' => $classes));
      $tmp = $struct->getNode(0);
      if(!$tmp->getField('href') && !$tmp->getField('contents') && (count($tmp->getFields()) > 1))
         $struct->fieldsFromFirstNode();

      break;

   case stristr($_REQUEST['data'],'<rss'):
      $struct = new OutlineFromXML($_REQUEST['data'],array('rootel' => 'rss','itemel' => 'channel>item','collapsels' => array('title','description')));
      break;
   case stristr($_REQUEST['data'],'<rdf'):
      $struct = new OutlineFromXML($_REQUEST['data'],array('rootel' => 'rdf:RDF','itemel' => 'item','collapsels' => array('title','description')));
      break;
   case stristr($_REQUEST['data'],'<feed'):
      $struct = new OutlineFromXML($_REQUEST['data'],array('rootel' => 'feed','itemel' => 'entry','collapsels' => array('title','content','summary')));
      break;
   case !checkXML($_REQUEST['data']):
      $struct = new OutlineFromXML($_REQUEST['data']);
      break;
   default:
      $struct = new OutlineFromJSON($_REQUEST['data']);
}//end switch TRUE

if($_REQUEST['urlfill']) {$struct->toOPMLfields();$struct->fillURLs();}//if urlfill, then ensure xml and html URLs by calling ->fillURLs.  The toOPMLfields is not necessary but helps fillURLs do it's job easier.

if($_REQUEST['output'] == 'opml') {//output OPML
   header('Content-Type: application/xml;charset=utf-8');//send the XML http header
   $struct->toOPMLfields();//convert fields to standard OPML field names
   if($_REQUEST['simplify']) {$struct->simplify();}//if we're simplifying, do so
   echo $struct->toOPML();//echo the OPML code for the object
   exit;//we're done
}//end if output == opml
if($_REQUEST['output'] == 'xoxo') {//output XOXO
   if(count($classes) < 1) {$classes = array('xoxo');}//If we were not passed classes, output has default class of 'xoxo'
   header('Content-Type: application/xml;charset=utf-8');//sent the XML http header (should be HTML or XHTML, but is XML so Ning stuff doesn't get output and mess it up)
   echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n";//Output XHTML 1.1 doctype so that &nbsp; etc is legal
   $struct->toXOXOFields();//convert fields to standard XOXO field names
   if($_REQUEST['simplify']) {$struct->simplify();}//if we're simplifying, do so
   echo $struct->toXOXO($classes);//output XOXO markup for the object (with given classes)
   exit;//we're done
}//end if output == xoxo
if($_REQUEST['output'] == 'json') {//output JSON
   if(count($classes) < 1) {$classes = array('nodes');}//Classes are used as the field name for subnodes.  If no classes were passed, use 'nodes'
   header('Content-Type: text/javascript;charset=utf-8');//output JavaScript http header
   if($_REQUEST['simplify']) {$struct->simplify();}//if we're simplifying, do so
   if(is_a($struct->getField('channel'),'Outline')) {//feed2json hack -- if there is a field named 'channel', and it is an Outline object (ie, has sub-fields, this happens for RSS 1.0 channel data)
      $channel = $struct->getField('channel');//extract the channel field
      foreach($channel->getFields() as $name => $val)//and copy all of it's fields into the main object (a la RSS 2.0)
         $struct->addField($name,$val);
      $struct->unsetField('channel');//kill the channel field
   }//end if channel
   $tmp = $struct->getNode(0);//temporary variable for first node
   if(is_a($tmp,'Outline') && (!$tmp->getField('link') || is_a($tmp->getField('link'),'Outline'))) {//feed2json hack, if the first node has no link field or link fiels is an Outline
      foreach($struct->getNodes() as $id => $nodes) {//assuming ATOM-style links, go through all subnodes and set the link field equal to the href field of the link field (because ATOM link fields have multiple fields, instead of being just the URL)
         if(!$tmp->getField('link'))
            $nds = $nodes->getNodes();
         else
            $nds = $nodes->getField('link')->getNodes();
         foreach($nds as $node) {
            if($node->getField('rel') == 'alternate') {
               $struct->_subnodes[$id]->setField('link',$node->getField('href'));
               break;
            }//end if
         }//end foreach nodes
      }//end foreach stuct
   }//end if ! link || Outline
   if($_REQUEST['callback'])//if we were passed a callback, output it
      echo $_REQUEST['callback'].'(';
   echo $struct->toJSON(implode(' ',$classes));//output the JSON markup for the object
   if($_REQUEST['callback'])//if we were passed a callback, finish the ()
      echo ');';
   exit;//we're done
}//end if output == json

?>