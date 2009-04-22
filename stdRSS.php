<?php

if(!($_REQUEST['url'] || $_REQUEST['data'])) {
   ?>
<p>
This script will take any RSS, ATOM, hAtom, or XOXO Blog Format feed (as well as some Active Channel content and other miscellaneous feed formats which are not 100% supported) and output a 'standardised' core RSS 2.0 rendration of the data.  The purpose of this tool is to provide working RSS 2.0 feeds for parsers who only want to deal with one set of field names.  For example, the author name in an ATOM feed because dc:creator in the RSS 2.0 output.
</p>

<b>From URL:</b>
<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>"><div>
   Enter a URL: <input type="text" name="url" value="" /><br />
   <input type="submit" name="submit" value="Convert" />
</div></form>
<br /><br />
<b>From Data:</b>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>"><div>
   <textarea rows="15" cols="60" name="data"></textarea><br />
   <input type="submit" name="submit" value="Convert" />
</div></form>

   <?php
   exit;
}//end if !url||data

if($_REQUEST['url'])
   $_REQUEST['data'] = file_get_contents($_REQUEST['url']);
require('std_feed_parse.php');
$data = std_feed_parse($_REQUEST['data']);

//RSS OUTPUT

header('Content-Type: application/xml;charset=utf-8');

require('std_rss_out.php');
echo std_rss_out($data);

?>