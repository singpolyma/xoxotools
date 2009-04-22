<?php

if(!$_REQUEST['url']) {
   ?>
<h2>POSH Analysis Tool</h2>
<form method="get">
   URL to analyze: <input type="text" name="url" />
</form>
   <?php
   exit;
}//end if ! url

header('Content-type: text/plain');

require_once 'is_valid_xml.php';
require_once 'is_valid_xhtml.php';

$page = file_get_contents($_REQUEST['url']);

echo 'Results for '.$_REQUEST['url'];
echo 'Is well formed XML? '.(is_valid_xml($page) ? 'Yes' : 'No');
echo 'Is valid (X)HTML? '.(is_valid_xhtml($_REQUEST['url']) ? 'Yes' : 'No');


?>