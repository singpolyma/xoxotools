<?php

   require_once(dirname(__FILE__).'/Outline.php');
   require_once(dirname(__FILE__).'/JSON.php');//requires JSON-PHP
   
class OutlineFromJSON extends Outline {

   function __construct($data='',$options=array()) {
      $json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
      $phpobj2 = $json->decode($data);
      parent::__construct($phpobj2);
      $options['itemel'] = $options['itemel'] ? strtolower($options['itemel']) : '';
      $this->nodesFromField($options['itemel'],true);
   }//end constructor
   
}//end class OutlineFromJSON

?>