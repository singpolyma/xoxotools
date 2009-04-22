<?php

if(!$_GET['url']) {
   ?>
   Turns a feed into <a href="http://blogxoxo.blogspot.com/2006/01/xoxo-blog-format.html">XOXO Blog Format</a> - compatible <a href="http://microformats.org/wiki/hatom">hAtom</a>
   <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>"><div>
      <input type="hidden" name="xn_auth" value="no" />
      Feed URL: <input type="text" name="url" value="" />
      <input type="submit" value="Go" />
   </div></form>
   <?php
   exit;
}//end if ! url

header('Content-Type: application/xml; charset=utf-8');

require_once 'feed2hatom.inc.php';
require_once 'OutlineClasses/Outline.php';

$outline = new Outline(feed2hatom($_GET['url']));
$outline->fieldsFromFirstNode(array());

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'."\n";
echo $outline->toXOXO(array('xoxo','posts','hfeed'),true,array('hentry'));

?>