<?php

   require_once(dirname(__FILE__).'/Outline.php');
   
class OutlineFromXML extends Outline {

   var $_errorcode = 0;

   function getError() { return $this->_errorcode; }

   function __construct($data='',$options=array()) {
      $theParser = xml_parser_create();
      if(!xml_parse_into_struct($theParser,$data,$vals)) {
         $errorcode = xml_get_error_code($theParser);
         if($errorcode != XML_ERROR_NONE && $errorcode != 27)
            $this->_errorcode = $errorcode;
      }//end if ! parse
      xml_parser_free($theParser);
      $flattento = '';
      $flattendat = '';
      $flattentag = '';
      $flattenattr = array();
      $subflatten = 0;
      $options['rootel'] = $options['rootel'] ? strtoupper($options['rootel']) : '';
      $options['itemel'] = $options['itemel'] ? strtolower($options['itemel']) : '';
      $options['collapsels'] = $options['collapsels'] ? $options['collapsels'] : array();
      foreach($vals as $el) {
         if(!$options['rootel'])
            $options['rootel'] = $el['tag'];
         $isopen = ($el['type'] == 'open' || $el['type'] == 'complete');//for readability
         $isclose = ($el['type'] == 'close' || $el['type'] == 'complete');
         if($options['rootel'] == $el['tag'] && $isclose)
            continue;
         if($flattento) {//if flattening tags
            if($isopen && $flattentag == $el['tag']) {$subflatten++;}
            if($isclose && $flattentag == $el['tag']) {
               if($subflatten) {
                  $subflatten--;
               } else {
                  if(!in_array($flattento,$options['collapsels'])) {
                     $flattendat = '<OutlineFromXML>'.$flattendat.'</OutlineFromXML>';
                     $tmp = explode('>',$options['itemel']);
                     if($tmp[1])
                        $flattendat = new OutlineFromXML($flattendat,array('rootel' => 'OutlineFromXML','itemel' => $tmp[1],'collapsels' => $options['collapsels']));
                     else
                        $flattendat = new OutlineFromXML($flattendat,array('rootel' => 'OutlineFromXML','itemel' => $options['itemel'],'collapsels' => $options['collapsels']));
                     foreach($flattenattr as $name => $val)
                        $flattendat->addField($name,$val);
                  }//end if ! in_array
                  if(!$this->getField($flattento)) {
                     $this->addField($flattento,$flattendat);
                  } else {
                     $oldfield = $this->getField(strtolower($el['tag']));
                     if(!is_a($oldfield,'Outline'))
                        $oldfield = new Outline(array(array('text' => $oldfield)));
                     if(count($oldfield->getFields()))
                        $oldfield = new Outline(array($oldfield));
                     if(!is_a($flattendat,'Outline'))
                        $flattendat = new Outline(array(array('text' => $flattendat)));
                     $oldfield->addNode($flattendat);
                     $this->setField(strtolower($el['tag']),$oldfield);
                  }//end if-else ! getField
                  $flattendat = '';
                  $flattentag = '';
                  $subflatten = 0;
                  $flattento = '';
                  $flattenattr = array();
                  continue;
               }//end if-else subflatten
            }//end if isclose &&
            $emptytag = false;//assume not an empty tag
            if($isopen) {//if opening tag
               $flattendat .= '<'.strtolower($el['tag']);//add open tag
               if($el['attributes']) {//if attributes
                  foreach($el['attributes'] as $id => $val) {//loop through and add
                     $flattendat .= ' '.strtolower($id).'="'.htmlspecialchars($val).'"';
                   }//end foreach
               }//end if attributes
               $emptytag = ($el['type'] == 'complete' && !$el['value']);//is emptytag?
               $flattendat .= $emptytag?' />':'>';//end tag
               if($el['value']) {$flattendat .= htmlspecialchars($el['value']);}//add contents, if any
            }//end if isopen
            if($el['type'] == 'cdata') {//if cdata
               $flattendat .= htmlspecialchars($el['value']);//add data
            }//end if cdata
            if($isclose) {//if closing tag
               if(!$emptytag) {$flattendat .= '</'.strtolower($el['tag']).'>';}//if not emptytag, write out end tag
            }//end if isclose
            continue;
         }//end if flattento
         if($el['type'] == 'complete') {
            if(!in_array(strtolower($el['tag']),$options['collapsels'])) {
               if($el['attributes']) {
                  if($el['value'])
                     $el['value'] = new Outline(array('text' => $el['value']));
                  else
                     $el['value'] = new Outline();
                  foreach($el['attributes'] as $id => $val) {
                     $el['value']->addField(strtolower($id),$val);
                  }//end foreach attributes
               }//end if attributes
            }//end if ! collapsels
            if(!$this->getField(strtolower($el['tag']))) {
               $this->addField(strtolower($el['tag']),$el['value']);
            } else {
               $oldfield = $this->getField(strtolower($el['tag']));
               if(!is_a($oldfield,'Outline'))
                  $oldfield = new Outline(array(array('text' => $oldfield)));
               if(count($oldfield->getFields()))
                  $oldfield = new Outline(array($oldfield));
               if(!is_a($el['value'],'Outline'))
                  $el['value'] = new Outline(array('text' => $el['value']));
               $oldfield->addNode($el['value']);
               $this->setField(strtolower($el['tag']),$oldfield);
            }//end if-else getField
            continue;
         }//end if type == complete
         if($el['type'] == 'cdata') {
            if($options['rootel'] == 'OUTLINEFROMXML')
               $el['tag'] = 'TEXT';
            if(!$this->getField(strtolower($el['tag']))) {
               if(trim($el['value']))
                  $this->addField(strtolower($el['tag']),$el['value']);
            } else
               $this->setField(strtolower($el['tag']),$this->getField(strtolower($el['tag'])).$el['value']);
            continue;
         }//end if type == complete
         if($el['type'] == 'open') {
            if($options['rootel'] == $el['tag']) {
               if($options['rootel'] == 'OUTLINEFROMXML')
                  $el['tag'] = 'TEXT';
               if($el['attributes']) {
                  foreach($el['attributes'] as $id => $val)
                     $this->addField(strtolower($id),$val);
               }//end if attributes
               if(trim($el['value']))
                  $this->addField(strtolower($el['tag']),$el['value']);
               continue;
            }//end if rootel
            if($el['attributes']) {
               foreach($el['attributes'] as $id => $val)
                  $flattenattr[strtolower($id)] = $val;
            }//end if attributes
            $flattento = strtolower($el['tag']);
            $flattentag = $el['tag'];
            $flattendat = $el['value'];
            continue;
         }//end if open
      }//end foreach vals

      $tmp = explode('>',$options['itemel']);
      if($options['itemel'] !== false)
         $this->nodesFromField($tmp[0]);
   }//end constructor
}//end class OutlineFromXML
   
?>