<?php

   require_once 'OutlineClasses/OutlineFromXOXO.php';
   require_once 'xn-app://singpolymaplay/getTidy.php';

   if($_GET['url']) {
      if($_GET['url'] == 'refer') $_GET['url'] = $_SERVER['HTTP_REFERRER'];
      $data = file_get_contents($_GET['url']);
   } elseif($_GET['data']) {
      $data = $_GET['data'];
   } else {
      ?>
      <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>"><div>
         Enter a<br />
         URL: <input type="text" name="url" /><br />
         or<br />
         Data: <textarea name="data"></textarea><br />
         <input type="submit" name="submit" value="Validate!" />
      </div></form>
      <?php
      exit;
   }//end if-elseif-else
   $struct = new OutlineFromXOXO($data);
   if($struct->getError()) {
      echo '<h2 style="text-align:center;"><a href="http://www.hcrc.ed.ac.uk/~richard/xml-check.cgi?url='.urlencode($_GET['url']).'">Not Valid XML!</a> -- '.xml_error_string($struct->getError()) . ' -- Error #' . $struct->getError().'</h2>';
      $data = getTidy($_GET['url']);
      $struct = new OutlineFromXOXO($data);
   }//end if error

   if($struct->getNumNodes() || count($struct->getFields())) {
      echo '<h2 style="text-align:center;">Valid <a href="http://microformats.org/wiki/xoxo">XOXO</a> data found!</h2>';
   } else {
      echo '<h2 style="text-align:center;">No valid <a href="http://microformats.org/wiki/xoxo">XOXO</a> data found</h2>';
   }//end if count || count
  
?>