<?php

function extract_by_class($xml,$class) {

   $rtrn = array();

   $theParser = xml_parser_create();
   if(!xml_parse_into_struct($theParser,$xml,$vals)) {
      $errorcode = xml_get_error_code($theParser);
      if($errorcode != XML_ERROR_NONE && $errorcode != 27)
         return $errorcode;
   }//end if ! parse
   xml_parser_free($theParser);

   $flattento = false;
   $flattentag = '';
   $subflatten = 0;

   foreach($vals as $el) {
      $isopen = ($el['type'] == 'open' || $el['type'] == 'complete');//for readability
      $isclose = ($el['type'] == 'close' || $el['type'] == 'complete');

               if($flattento !== false) {//if flattening tags
                  if($isopen && $flattentag == $el['tag']) {$subflatten++;}
                  if($isclose && $flattentag == $el['tag']) {
                     if($subflatten > 0) {
                        $subflatten--;
                     } else {
                        $flattento .= '</'.strtolower($flattentag).'>';
                        $rtrn[] = $flattento;
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
                  continue;
               }//end if flattento

      if($isopen && $el['attributes']['CLASS'] && in_array($class,explode(' ',$el['attributes']['CLASS']))) {//if we've found the right class
         $flattento = '<'.strtolower($el['tag']);
         foreach($el['attributes'] as $att => $val)
            $flattento .= ' '.htmlspecialchars(strtolower($att)).'="'.htmlspecialchars($val).'"';
         $flattento .= '>'.htmlspecialchars($el['value']);
         $flattentag = $el['tag'];
         $subflatten = 0;
         if($isclose) {
            $flattento .= '</'.strtolower($flattentag).'>';
            $rtrn[] = $flattento;
            $flattentag = '';
            unset($flattento);
            $flattento = false;
            $subflatten = 0;
         }//end if isclose
      }//end if theclass

   }//end foreach vals as el

   return $rtrn;

}//end function extract_by_class

if(isset($_REQUEST['_microsummary'])) {
   header('Content-type: text/plain;');
   require_once 'xn-app://singpolymaplay/getTidy.php';
   $tmp = extract_by_class(getTidy($_REQUEST['url']),$_REQUEST['class']);
   echo str_replace('  ',' ',trim(str_replace("\n",' ',str_replace("\r",'',strip_tags($tmp[0])))));
}//end if _microsummary

?>