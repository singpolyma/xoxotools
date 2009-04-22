<?php

   require_once(dirname(__FILE__).'/Outline.php');
   
   class OutlineFromXOXO extends Outline {

   var $_errorcode = 0;

   function getError() { return $this->_errorcode; }

   function __construct($data='',$options=array()) {
      if(!$options['classes'] && !is_array($options['classes'])) $options['classes'] = array('xoxo');
      $data = $this->xoxo2array($data,$options['classes']);
      parent::__construct($data);
      foreach($this->_fields as $name => $val) {
         if(substr(trim($val),0,3) == '<ul' || substr(trim($val),0,3) == '<ol')
            $this->_fields[$name] = new OutlineFromXOXO($val);
      }//end foreach
   }//end constructor
   
   //private functions
   function xoxo2array($data,$classes=array('xoxo')) {
      $theParser = xml_parser_create();
      if(!xml_parse_into_struct($theParser,$data,$vals)) {
         $errorcode = xml_get_error_code($theParser);
         if($errorcode != XML_ERROR_NONE && $errorcode != 27)
            $this->_errorcode = $errorcode;
      }//end if ! parse
      xml_parser_free($theParser);
      $rtrn = array();
      $subroots = 0;
      $nestlevel = 0;
      $parents = array();
      $thisel = array();
      $altaid = 0;
      $curdt = '';
      $opentag = FALSE;
      $waitforLI = false;
      foreach($vals as $el) {
        if($el['tag'] == 'UL' || $el['tag'] == 'OL') {
           $inxoxotmp = TRUE;
           foreach($classes as $class) {
              $inxoxotmp = $inxoxotmp && stristr($el['attributes']['CLASS'],$class);
           }//end foreach
        }//end if tag = UL || OL
        if(!$inxoxo && $inxoxotmp) {$inxoxo = TRUE;$inxoxotmp = FALSE;$rootel = $el['tag'];continue;}//if we are starting the xoxo element set inxoxo to TRUE, rootel to the kind of element used as the xoxo root and skip this element
        if($inxoxo && (!$opentag || $opentag == 'LI') && $el['tag'] == $rootel && $el['type'] == 'open') {$subroots++;}//if we are opening a nested element of the same type as root, note this
        if($inxoxo && (!$opentag || $opentag == 'LI') && $el['tag'] == $rootel && $el['type'] == 'close') {//if we are closing the root element type and are in the xoxo element
           if($subroots) {//if there are subroots, we're closing one of those
              $subroots--;
           } else {//otherwise were closing THE root
              $inxoxo = FALSE;
           }//end if subroots
        }//end if inxoxo && rootel && close
        if($inxoxo) {//if we are inside the xoxo element
           if($el['tag'] == 'LI') {//if this is an LI tag
              if($opentag == 'LI' && $el['type'] == 'close') {$opentag = FALSE;}
              if(($el['type'] == 'open' || $el['type'] == 'complete') && !$opentag) {//if we are opening the tag
                 $parseda = FALSE;
                 $opentag  = ($el['type'] == 'open')?'LI':FALSE;
                 $altaid = 0;
                 $waitforLI = false;
                 if($nestlevel) {//if we are in another LI element
                    unset($parents[$nestlevel]);
                    $parents[$nestlevel] =& $thisel;//set the parent element
                    $thisel =& $thisel[];//create a new element in this el and assign it's reference to thisel
                 } else {//otherwise this is a root element
                    $parents[0] =& $rtrn;//set the parent element
                    unset($thisel);
                    $thisel =& $rtrn[];
                 }//end if-else nestlevel
                 if($el['attributes']['CLASS']) {$thisel['class'] = $el['attributes']['CLASS'];}//if there is a class, get it
                 if(trim($el['value'])) {$thisel['text'] = $el['value'];}//if there is a value, get it and assign trimmed to text
                 $nestlevel++;//we are nested one more level
                 if($el['type'] == 'open') {continue;}
              }//end if open
              if(($el['type'] == 'close' || $el['type'] == 'complete') && !$opentag) {//if we are closing the tag
                 $nestlevel--;//we are nested one less level
                 unset($thisel);
                 $thisel =& $parents[$nestlevel];//we are now back in the parent
                 $opentag = FALSE;
                 $waitforLI = true;
              }//end if close
           }//end if li
           if($waitforLI) {continue;}
           if(($el['tag'] == 'DL' && $el['type'] == 'open') && (!$opentag || $opentag == 'LI')) {//if this is opening a DL tag
              continue;//skip this, to keep a-less LIs from getting a <dl> tacked onto their TEXT property
           }//end if tag == DL && type == open ...
           if($el['tag'] == 'A') {//if this is an A tag
              if(($el['type'] == 'open' || $el['type'] == 'complete') && (!$opentag || $opentag == 'LI')) {
                 if($parseda) {
                    $altaid++;
                    if(trim($el['value'])) {$thisel['text#'.$altaid] = $el['value'];}//if there is a value, get it and assign trimmed to text
                    if(trim($el['attributes']['REL'])) {$thisel['rel#'.$altaid] = trim($el['attributes']['REL']);}//if there is a rel, get it trimmed
                    if(trim($el['attributes']['HREF'])) {$thisel['href#'.$altaid] = trim($el['attributes']['HREF']);}//if there is an href, get it trimmed
                    if(trim($el['attributes']['TITLE'])) {$thisel['title#'.$altaid] = trim($el['attributes']['TITLE']);}//if there is a rel, get it trimmed
                    if(trim($el['attributes']['TYPE'])) {$thisel['type#'.$altaid] = trim($el['attributes']['TYPE']);}//if there is a type, get it trimmed
                    if(trim($el['attributes']['REV'])) {$thisel['rev#'.$altaid] = trim($el['attributes']['REV']);}//if there is a rev, get it trimmed
                    if($el['attributes']['CLASS']) {$thisel['class#'.$altaid] = $el['attributes']['CLASS'];}//if there is a class, get it
                    $opentag  = ($el['type'] == 'open')?'Aalt':FALSE;
                 } else {
                    if(trim($el['value'])) {$thisel['text'] = $el['value'];}//if there is a value, get it and assign trimmed to text
                    if(trim($el['attributes']['REL'])) {$thisel['rel'] = trim($el['attributes']['REL']);}//if there is a rel, get it trimmed
                    if(trim($el['attributes']['HREF'])) {$thisel['href'] = trim($el['attributes']['HREF']);}//if there is an href, get it trimmed
                    if(trim($el['attributes']['TITLE'])) {$thisel['title'] = trim($el['attributes']['TITLE']);}//if there is a rel, get it trimmed
                    if(trim($el['attributes']['TYPE'])) {$thisel['type'] = trim($el['attributes']['TYPE']);}//if there is a type, get it trimmed
                    if(trim($el['attributes']['REV'])) {$thisel['rev'] = trim($el['attributes']['REV']);}//if there is a rev, get it trimmed
                    if($el['attributes']['CLASS']) {$thisel['class'] = $el['attributes']['CLASS'];}//if there is a class, get it
                    $parseda = TRUE;
                    $opentag  = ($el['type'] == 'open')?'A':FALSE;
                 }//end if-else parseda
                 continue;
              }//end if type == open OR complete && !parseda
              if(($opentag == 'A' || $opentag == 'Aalt') && $el['type'] == 'close') {$opentag = FALSE;}
           }//end if a 
           if($el['tag'] == 'DT') {//if this is a DT tag
              if(($el['type'] == 'open' || $el['type'] == 'complete') && (!$opentag || $opentag == 'LI')) {
                 $curdt = $el['value'];
                 $opentag  = ($el['type'] == 'open')?'DT':FALSE;
                 continue;
              }//end if type == open || complete
              if($opentag == 'DT' && $el['type'] == 'close') {$opentag = FALSE;}
           }//end if dt
           if($el['tag'] == 'DD') {//if this is a DD tag
              if(($el['type'] == 'open' || $el['type'] == 'complete') && (!$opentag || $opentag == 'LI')) {
                 if($el['type'] != 'open') $el['value'] = $el['value'];
                 $thisel[$curdt] = htmlspecialchars($el['value']);
                 $opentag  = ($el['type'] == 'open')?'DD':FALSE;
                 continue;
              }//end if type == open || complete
              if($opentag == 'DD' && $el['type'] == 'close') {
                 $opentag = FALSE;
              }//end if opentag == DD && type == close
           }//end if dd
           if($opentag) {//if we are in a structural tag already
              $tmp = '';
              $emptytag = FALSE;
              if($el['type'] == 'open' || $el['type'] == 'complete') {
                 $tmp .= '<'.strtolower($el['tag']);
                 if($el['attributes']) {
                    foreach($el['attributes'] as $id => $val) {
                       $tmp .= ' '.strtolower($id).'="'.htmlspecialchars($val).'"';
                    }//end foreach
                 }//end if attributes
                 $emptytag = ($el['type'] == 'complete' && !$el['value']);
                 if(strtolower($el['tag']) == 'div' || strtolower($el['tag']) == 'p') $emptytag = false;
                 $tmp .= $emptytag?' />':'>';
                 if(trim($el['value'])) {$tmp .= htmlspecialchars($el['value']);}
              }//end if open || complete
              if($el['type'] == 'cdata') {
                 $tmp .= htmlspecialchars($el['value']);
              }//end if cdata
              if($el['type'] == 'close' || $el['type'] == 'complete') {
                 if(!$emptytag) {$tmp .= '</'.strtolower($el['tag']).'>';}
              }//end if close
              if($opentag == 'A' || $opentag == 'LI') {//if in a structural A tag or the main LI tag
                 $thisel['text'] .= $tmp;
              }//end if opentag == A || LI
              if($opentag == 'Aalt') {//if in an alternate A tag
                 $thisel['text#'.$altaid] .= $tmp;
              }//end if opentag == Aalt
              if($opentag == 'DT') {//if in a structural DT
                 $curdt .= $tmp;
              }//end if opentag == DT
              if($opentag == 'DD') {//if in a structural DD
                 $thisel[$curdt] .= $tmp;
              }//end if opentag == DD
              unset($tmp);
              continue;
           }//end if opentag
        }//end if inxoxo
      }//end foreach
      return $rtrn;
   }//end function xoxo2array

}//end class OutlineFromXOXO
   
?>