<?php

header('Content-type: text/plain');

$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
//$doc->loadHTML(file_get_contents('http://www2.blogger.com/profile/14267910391550235126'));
$doc->loadHTML(file_get_contents('http://www.blogger.com/profile/08406820780522358646'));
$xpath = new DOMXPath($doc);
$results = $xpath->query('//tr[not(@class)]//th[not(@class)]//a');

foreach($results as $item)
   echo $item->nodeValue.' -- '.$item->getAttribute('href')."\n";


?>