<?php

function is_valid_xhtml($url) {
   $page = file_get_contents('http://validator.w3.org/check?uri='.urlencode($url));
   return (strstr($page,'<h2 class="valid">This Page Is Valid XHTML 1.0 Strict!</h2>')) ? true : false;
}//end function is_valid_xhtml

?>