<?php

   require_once(dirname(__FILE__).'/Outline.php');
   require_once(dirname(__FILE__).'/OutlineFromXOXO.php');
   
class OutlineFromHATOM extends Outline {

   var $_errorcode = 0;

   function getError() { return $this->_errorcode; }

   function __construct($data='',$options=array()) {
      $data = $this->hatom2array($data,$options['resolve']);
      parent::__construct($data);
   }//end constructor
   
   //private functions
   function hatom2array($data,$resolve) {

      $pageurl = $resolve;

      $tmp = explode('/',$resolve);
      if(strstr(array_pop($tmp),'.'))
         $resolve = implode('/',$tmp);
      if(substr($resolve,-1,1) != '/')
         $resolve .= '/';

      $theParser = xml_parser_create();
      if(!xml_parse_into_struct($theParser,$data,$vals)) {
         $errorcode = xml_get_error_code($theParser);
         if($errorcode != XML_ERROR_NONE && $errorcode != 27)
            $this->_errorcode = $errorcode;
      }//end if ! parse
      xml_parser_free($theParser);
      $rtrn = array();
      $inhatom = true;
      $rootel = '';
      $subroots = 0;
      $entryel = false;
      $subentry = 0;
      $flattento = false;
      $flattentag = '';
      $subflatten = 0;
      $entry = array();
      $tmptitle = '';
      $toabbr = false;
      $enteringentry = false;

      foreach($vals as $el) {

         if($el['attributes']['HREF']) {
            $el['attributes']['HREF'] = trim($el['attributes']['HREF']);
            if($el['attributes']['HREF']{0} == '/') $el['attributes']['HREF'] = substr($el['attributes']['HREF'],1,strlen($el['attributes']['HREF'])-1);
            if(substr($el['attributes']['HREF'],0,4) != 'http' && $resolve) $el['attributes']['HREF'] = $resolve.$el['attributes']['HREF'];
         }//end if href

         if($el['tag'] == 'TITLE') {$this->setField('text',trim($el['value']));$this->setField('rel','home');}

         $isopen = ($el['type'] == 'open' || $el['type'] == 'complete');//for readability
         $isclose = ($el['type'] == 'close' || $el['type'] == 'complete');

         if(!$inhatom && in_array('hfeed',explode(' ',$el['attributes']['CLASS'])) && $isopen) {//are we starting an hatom section?
            $inhatom = true;
            $rootel = $el['tag'];
         }//end if !inhatom and class~=hfeed

         if($inhatom) {//if processing an hatom section

            if($el['attributes']['REL'] && in_array('tag',explode(' ',$el['attributes']['REL']))) {
               $atag = array_reverse(explode('/',$el['attributes']['HREF']));
               $atag = explode('#',$atag[0]);
               $atag = explode('?',$atag[0]);
               $entry['tags'][] = $atag[0];
            }//end if rel=tag

            if($el['tag'] == $rootel && $isopen) {$subroots++;}
            if($el['tag'] == $rootel && $isclose) {
               if($subroots)
                  $subroots--;
               else
                  $inhatom = false;
            }//end if tag == rootel && type == close
            if(!$entryel && in_array('hentry',explode(' ',$el['attributes']['CLASS'])) && $isopen) {
               $entryel = $el['tag'];
               $entry = array();
               $entry['class'] = 'entry-title';
               $entry['rel'] = 'bookmark';
               if($el['attributes']['ID'])
                  $entry['id'] = $el['attributes']['ID'];
               $tmptitle = '';
               $subentry = 0;
               $enteringentry = true;
            }//end if !entryel and class~=hentry
            if($entryel) {//if in an entry
               if($flattento !== false) {//if flattening tags
                  if($isopen && $flattentag == $el['tag']) {$subflatten++;}
                  if($isclose && $flattentag == $el['tag']) {
                     if($subflatten > 0) {
                        $subflatten--;
                     } else {
                        $flattentag = '';
                        $subflatten = 0;
                        unset($flattento);
                        $flattento = false;
                     }//end if-else subflatten
                  }//end if isclose &&
                  if($flattento !== false) {//flattento may have changed in previous section
                     $emptytag = false;//assume not an empty tag
                     if($isopen) {//if opening tag
                        $flattento .= ' <'.strtolower($el['tag']);//add open tag
                        if($el['attributes']) {//if attributes
                           foreach($el['attributes'] as $id => $val) {//loop through and add
                              $flattento .= ' '.strtolower($id).'="'.htmlspecialchars($val).'"';
                           }//end foreach
                        }//end if attributes
                        $emptytag = ($el['type'] == 'complete' && !$el['value']);//is emptytag?
                        $flattento .= $emptytag?' />':'>';//end tag
                        if($el['value']) {$flattento .= htmlspecialchars($el['value']);}//add contents, if any
                     }//end if isopen
                     if($el['type'] == 'cdata') {//if cdata
                        $flattento .= htmlspecialchars($el['value']);//add data
                     }//end if cdata
                     if($isclose) {//if closing tag
                        if(!$emptytag) {$flattento .= '</'.strtolower($el['tag']).'>';}//if not emptytag, write out end tag
                     }//end if isclose
                  }//end if flattento
               }//end if flattento
               //THIS ABBR SECTION MAY BE OBSOLETE
               if($toabbr !== false && $el['tag'] == 'ABBR') {
                  $toabbr = strtotime(trim($el['attributes']['TITLE']));
                  unset($toabbr);
                  $toabbr = false;
               }//end if toabbr
               //close entry?
               if(!$enteringentry && $el['tag'] == $entryel && $isopen) {$subentry++;}
               if($el['tag'] == $entryel && $isclose) {
                  if($subentry) {
                     $subentry--;
                  } else {
                     if(stristr($entry['text#1'],'<')) {//process author
                        $theParser = xml_parser_create();
                        xml_parse_into_struct($theParser,'<cnt>'.$entry['text#1'].'</cnt>',$tmp);
                        xml_parser_free($theParser);
                        foreach($tmp as $tmpel) {
                           if(in_array('fn',explode(' ',$tmpel['attributes']['CLASS']))) {$entry['text#1'] = trim($tmpel['value']);}
                           if(in_array('url',explode(' ',$tmpel['attributes']['CLASS']))) {$entry['href#1'] = $tmpel['attributes']['HREF'] ? trim($tmpel['attributes']['HREF']) : trim($tmpel['value']);}
                        }//end foreach
                     }//end if (process author)
                     if($entry['comments']) {//process comments
                        $tmp = new OutlineFromXOXO('<ul class="xoxo comments">'.$entry['comments'].'</ul>',array('xoxo','comments'));
                        $entry = array_merge($entry,$tmp->toArray());
                        unset($entry['comments']);
                     }//end if comments
                     $entry['title'] = $entry['title'] ? $entry['title'] : $entry['updated'];
                     $entry['updated'] = $entry['updated'] ? $entry['updated'] : $entry['title'];
                     $entry['text'] = $entry['text'] ? strip_tags($entry['text']) : $tmptitle;
                     if(!$entry['href'] && $entry['id'])
                        $entry['href'] = $pageurl.'#'.$entry['id'];
                     $rtrn[] = $entry;
                     unset($entry);
                     $entryel = false;
                     $flattentag = '';
                     $subflatten = 0;
                     unset($flattento);
                     unset($toabbr);
                     $flattento = false;
                     $toabbr = false;
                     $subentry = 0;
                     continue;
                  }//end if subentry
               }//end if tag == rootel && type == close
               //get title
               if(!$entry['text'] && in_array('entry-title',explode(' ',$el['attributes']['CLASS'])) && $isopen) {
                  if($el['tag'] == 'ABBR') {
                     $entry['text'] = trim($el['attributes']['TITLE']);
                  } else {
                     $entry['text'] .= $el['value'];
                     if(!$isclose) {//if there are tags in this tag
                        $flattento =& $entry['text'];
                        $flattentag = $el['tag'];
                     }//end if ! isclose
                  }//end if-else ABBR
               }//end if entry-title isopen
               if(!$entry['text'] && strlen($el['tag']) == 2 && $el['tag']{0} == 'h' && is_numeric($el['tag']{1}) && $isopen) {//if starting h# tag
                  $tmptitle .= trim($el['value']);
               }//end if h#
               //get content
               if(in_array('entry-content',explode(' ',$el['attributes']['CLASS'])) && $isopen) {
                  $entry['body'] .= $el['value'];
                  if(!$isclose) {//if there are tags in this tag
                     $flattento =& $entry['body'];
                     $flattentag = $el['tag'];
                  }//end if ! isclose
               }//end if content
               //get summary
               if(in_array('entry-summary',explode(' ',$el['attributes']['CLASS'])) && $isopen) {
                  $entry['summary'] .= $el['value'];
                  if(!$isclose) {//if there are tags in this tag
                     $flattento =& $entry['summary'];
                     $flattentag = $el['tag'];
                  }//end if ! isclose
               }//end if excerpt isopen
               //get permalink
               if(!$entry['href'] && in_array('bookmark',explode(' ',$el['attributes']['REL'])) && $isopen) {
                  $entry['href'] = $el['attributes']['HREF'];
               }//end if bookmark isopen
               //get timestamp
               if(!$entry['title'] && in_array('published',explode(' ',$el['attributes']['CLASS'])) && $isopen) {
                  if(trim($el['value']) && strtotime(trim($el['value'])) > 0)
                     $entry['title'] = strtotime(trim($el['value']));
                  if(trim($el['attributes']['TITLE']) && strtotime(trim($el['attributes']['TITLE'])) > 0)
                     $entry['title'] = strtotime(trim($el['attributes']['TITLE']));
               }//end if published isopen
               //get updated
               if(!$entry['updated'] && in_array('updated',explode(' ',$el['attributes']['CLASS'])) && $isopen) {
                  if(trim($el['value']) && strtotime(trim($el['value'])) > 0)
                     $entry['updated'] = strtotime(trim($el['value']));
                  if(trim($el['attributes']['TITLE']) && strtotime(trim($el['attributes']['TITLE'])) > 0)
                     $entry['updated'] = strtotime(trim($el['attributes']['TITLE']));
               }//end if updated isopen
               //get author
               if(!$entry['text#1'] && (in_array('author',explode(' ',$el['attributes']['CLASS'])) || $el['tag'] == 'ADDRESS') && $isopen) {
                  $entry['rel#1'] = 'author';
                  if(trim($el['value'])) {
                     $entry['text#1'] = trim($el['value']);
                     if($el['attributes']['HREF']) {$entry['href#1'] = trim($el['attributes']['HREF']);}
                  }//end if
                  if(in_array('fn',explode(' ',$el['attributes']['CLASS'])))
                     $entry['text#1'] = trim(strip_tags($el['value']));
                  if(in_array('url',explode(' ',$el['attributes']['CLASS'])))
                     $entry['href#1'] = $el['attributes']['HREF'] ? trim($el['attributes']['HREF']) : trim($el['value']);
                  if(!$isclose) {//if there are tags in this tag
                     $flattento =& $entry['text#1'];
                     $flattentag = $el['tag'];
                  }//end if ! isclose
               }//end if author isopen
               //get comments URL and count
               if(!$entry['text#2'] && in_array('comments',explode(' ',$el['attributes']['REL'])) && !in_array('alternate',explode(' ',$el['attributes']['REL'])) && $isopen) {
                  $entry['rel#2'] = 'comments';
                  $entry['text#2'] = trim($el['value']);
                  $entry['href#2'] = trim($el['attributes']['HREF']);
               }//end if comments isopen
               //get comments feed URL
               if(!$entry['href#3'] && in_array('comments',explode(' ',$el['attributes']['REL'])) && in_array('alternate',explode(' ',$el['attributes']['REL'])) && $isopen) {
                  $entry['rel#3'] = 'comments alternate';
                  $entry['text#3'] = trim($el['value']);
                  $entry['href#3'] = trim($el['attributes']['HREF']);
               }//end if comments && alternate isopen
               //get comments
               if(in_array('xoxo',explode(' ',$el['attributes']['CLASS'])) && in_array('comments',explode(' ',$el['attributes']['CLASS'])) && $isopen) {
                  $entry['comments'] = '';
                  if(!$isclose) {//if there are tags in this tag
                     $flattento =& $entry['comments'];
                     $flattentag = $el['tag'];
                  }//end if ! isclose
               }//end if content isopen
               $enteringentry = false;
            }//end if entryel
         }//end if $inhatom
      }//end foreach
      return $rtrn;
   }//end function hatom2array

}//end class OutlineFromHATOM

?>