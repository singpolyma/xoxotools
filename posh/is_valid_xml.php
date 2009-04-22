<?php

function is_valid_xml($xml) {
   $theParser = xml_parser_create();
   if(!xml_parse_into_struct($theParser,$data,$vals)) {
      $errorcode = xml_get_error_code($theParser);
      if($errorcode != XML_ERROR_NONE && $errorcode != 27)
         return false;
   }//end if ! parse
   xml_parser_free($theParser);
   return true;
}//end function is_valid_xml

?>