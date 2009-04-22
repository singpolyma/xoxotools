<?php

   require_once(dirname(__FILE__).'/Outline.php');
   
class OutlineFromOPML extends Outline {

   var $_errorcode = 0;

   function getError() { return $this->_errorcode; }

   function __construct($data='',$options=array()) {
      $data = $this->opml2array($data);
      parent::__construct($data);
   }//end constructor
   
   //private functions
   function opml2array($data) {
      $theParser = xml_parser_create();
      if(!xml_parse_into_struct($theParser,$data,$vals)) {
         $errorcode = xml_get_error_code($theParser);
         if($errorcode != XML_ERROR_NONE && $errorcode != 27)
            $this->_errorcode = $errorcode;
      }//end if ! parse
      xml_parser_free($theParser);
      $rtrn = array();
      $meta = array();
      $nestlevel = 0;
      $parents = array();
      $thisel = array();
      $curdt = '';
      foreach($vals as $el) {
        if($el['tag'] == 'OPML') {$inopml = TRUE;continue;}//if we are starting the opml element set inopml to TRUE
        if($inopml && $el['tag'] == 'OPML' && $el['type'] == 'close') {$inopml = FALSE;}//if we are closing the OPML root element then !inopml
        if($inopml) {//if we are inside the OPML element
           if($el['tag'] == 'HEAD' && $el['type'] == 'open') {$inhead = TRUE;}//if entering head
           if($el['tag'] == 'HEAD' && $el['type'] == 'close') {$inhead = FALSE;}//if exiting head
           if($inhead && $el['type'] == 'complete') {//only process complete head elements
              $meta[strtolower($el['tag'])] = trim($el['value']);
           }//end if inhead && complete
           if($el['tag'] == 'OUTLINE') {//if this is an OUTLINE tag
              if($el['type'] == 'open' || $el['type'] == 'complete') {//if we are opening the tag
                 if($nestlevel) {//if we are in another OUTLINE element
                    unset($parents[$nestlevel]);
                    $parents[$nestlevel] =& $thisel;//set the parent element
                    $thisel =& $thisel[];//create a new element in this el and assign it's reference to thisel
                 } else {//otherwise this is a root element
                    $parents[0] =& $rtrn;//set the parent element
                    unset($thisel);
                    $thisel =& $rtrn[];
                 }//end if-else nestlevel
                 foreach($el['attributes'] as $attribute => $val) {
                    if($thisel[strtolower($attribute)]) {continue;}
                    if(trim($val)) {$thisel[strtolower($attribute)] = trim($val);}
                 }//end foreach
                 $nestlevel++;//we are nested one more level
              }//end if open
              if($el['type'] == 'close' || $el['type'] == 'complete') {//if we are closing the tag
                 $nestlevel--;//we are nested one less level
                 unset($thisel);
                 $thisel =& $parents[$nestlevel];//we are now back in the parent
              }//end if close
           }//end if OUTLINE
        }//end if inopml
      }//end foreach
      foreach($meta as $att => $val) {
         $rtrn[$att] = $val;
      }//end foreach
      return $rtrn;
   }//end function opml2array
   
}//end class OutlineFromOPML
   
?>