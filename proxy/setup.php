<?php

if(!$_REQUEST['url']) {
   header('Content-type: text/plain');
   header('Location: http://xoxotools.ning.com/proxy/',true,302);
   exit;
}//end if ! url

if(!XN_Profile::current()->isLoggedIn())
   die('<h2>Please log in</h2>');

require('normalize_url.php');

$_REQUEST['url'] = normalize_url(trim($_REQUEST['url']));
$domain = explode('/',$_REQUEST['url']);
$domain = strtolower(str_replace('*','',$domain[2]));
$domain = explode('.',$domain);
if(count($domain) > 2)
   unset($domain[0]);
$domain = implode('.',$domain);

$related = XN_Query::create('Content')
         ->filter('owner','=')
         ->filter('type','eic','PageSetup')
         ->filter('description','eic',$domain);
$related = $related->execute();

$itself = XN_Query::create('Content')
         ->filter('owner','=')
         ->filter('type','eic','PageSetup')
         ->filter('title','=',$_REQUEST['url']);
$itself = $itself->execute();
if(!$itself || !count($itself) || !$itself[0])
   $itself = XN_Content::create('PageSetup',$_REQUEST['url'],$domain);
else
   $itself = $itself[0];

$records = unserialize($itself->my->microformats);
if(!$records || !is_array($records))
   $records = array();

echo '<h2>Setup Page for '.htmlentities($_REQUEST['url']).'</h2>'."\n";

echo '<h3>Related setups (from '.htmlentities($domain).', you may want one of these instead)</h3>'."\n";
echo '<ul>'."\n";
foreach($related as $item)
   echo '   <li><a href="setup.php?url='.htmlentities(urlencode($item->title)).'">'.htmlentities($item->title).'</a></li>'."\n";
echo '</ul>'."\n";

echo '<h3>Microformats on Page</h3>'."\n";
echo "<p>This is where you set up the data on the page that could be marked up with microformats, but isn't.</p>\n";

echo '<form method="get" action="edit.php"><div>';
echo '<input type="hidden" name="url" value="'.htmlentities($_REQUEST['url']).'" />';
echo 'Add Microformat: <select name="microformat">';
echo '<option value="hCard">hCard</option>';
echo '</select>';
echo '<input type="submit" value="Go" />';
echo '</div></form>';

echo '<ul>'."\n";
foreach($records as $id => $record)
   echo '<li><a href="edit.php?url='.htmlentities(urlencode($_REQUEST['url'])).'&amp;microformat='.htmlentities(urlencode($record['microformat'])).'&amp;id='.htmlentities(urlencode($id)).'">'.htmlentities($record['microformat']).' #'.htmlentities($id).'</a></li>';
echo '</ul>'."\n";

?>