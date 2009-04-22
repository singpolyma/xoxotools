<?php

if(!$_REQUEST['url'] || !$_REQUEST['microformat']) {
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

if($_REQUEST['id']) {
   $record = $records[$_REQUEST['id']];
} else {
   $tmp = array_reverse(array_keys($records));
   $_REQUEST['id'] = $tmp[0] + 1;
   $record = array();
}//end if-else id

if(isset($_REQUEST['save'])) {//if saving
   $record = array('microformat' => $_REQUEST['microformat']);
   foreach($_REQUEST['field'] as $id => $field)
      $record[] = array('field' => $field, 'xpath' => $_REQUEST['xpath'][$id]);
   $records[$_REQUEST['id']] = $record;
   $itself->my->set('microformats',serialize($records));
   $itself->save();
   header('Content-type: text/plain');
   header('Location: http://xoxotools.ning.com/proxy/setup.php?url='.urlencode($_REQUEST['url']),true,302);
   exit;
}//end if save

$microformats = array('hCard' => array('fn','n','nickname','family-name','given-name','additional-name','honorific-prefix','honorific-suffix','sort-string','url','email','type (email)','tel','type (tel)','adr','label','geo latitude','geo longitude','tz','title','role','org','category','note','class','key','mailer','uid','rev','photo','logo','sound','bday'));

?>

<script type="text/javascript">
//<![CDATA[
   function addField() {
      var div = document.createElement('div');
      div.innerHTML = '<select name="field[]" style="margin-right:10px;width:100px;"><?php foreach($microformats[$_REQUEST['microformat']] as $field)
         echo '<option value="'.htmlentities($field).'">'.htmlentities($field).'<\/option>';
?><\/select> <input style="width:300px;" type="text" name="xpath[]" \/>';
      var block = document.getElementById('theform');
      block.appendChild(div);
   }//end function
//]]>
</script>

<h2>Add <?php echo $_REQUEST['microformat']; ?></h2>
<a href="#" onclick="addField();return false;">Add Field</a>
<form method="post" action="edit.php?save&amp;url=<?php echo htmlentities(urlencode($_REQUEST['url'])); ?>&amp;microformat=<?php echo htmlentities(urlencode($_REQUEST['microformat'])); ?>&amp;id=<?php echo htmlentities(urlencode($_REQUEST['id'])); ?>"><div id="theform">
<div style="font-weight:bold;float:left;margin-right:20px;width:100px;">Field</div>
<div style="font-weight:bold;">XPath <input type="submit" value="Save" /> </div>
<?php

foreach($record as $id => $field) {
   if(!is_numeric($id)) continue;
   ?>
<div>
<select name="field[]" style="margin-right:10px;width:100px;"><?php
array_unshift($microformats[$_REQUEST['microformat']],$field['field']);
$microformats[$_REQUEST['microformat']] = array_unique($microformats[$_REQUEST['microformat']]);
foreach($microformats[$_REQUEST['microformat']] as $field2)
         echo '<option value="'.htmlentities($field2).'">'.htmlentities($field2).'</option>';
?></select> <input style="width:300px;" type="text" name="xpath[]" value="<?php echo htmlentities($field['xpath']); ?>" />
</div>
   <?php
}//end foreach

?>
</div></form>