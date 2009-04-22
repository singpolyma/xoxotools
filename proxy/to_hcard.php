<?php

//take a uf XPATH description and a DomDocument and echo an hCard
function to_hcard($uf,$doc) {
   echo '<div class="vcard">'."\n";
   foreach($uf as $id => $field) {
      if(!is_numeric($id)) continue;
      $xpath = new DOMXPath($doc);
      $results = $xpath->query($field['xpath']);
      $nameFields = array();
      $emailFields = array();
      $telFields = array();
      $geoFields = array();
      switch($field['field']) {
         case 'fn': case 'n': case 'nickname': case 'sort-string': case 'adr': case 'label': case 'tz': case 'title': case 'role': case 'org': case 'category': case 'class': case 'key': case 'mailer': case 'uid': case 'rev': //text-only fields
            foreach($results as $node)
               echo '   <div class="'.$field['field'].'">'.htmlentities($node->nodeValue).'</div>'."\n";
            break;
         case 'family-name': case 'given-name': case 'additional-name': case 'honorific-prefix': case 'honorific-suffix': //name fields
            $nameFields[$field['field']] = $results;
            break;
         case 'url':
            echo '   <ul>'."\n";
            foreach($results as $node)
               echo '      <li><a class="'.$field['field'].'" href="'.$node->getAttribute('href').'">'.htmlentities($node->nodeValue).'</a></li>'."\n";
            echo '   </ul>'."\n";
            break;
         case 'email':
            if(!$emailFields[$field['type']]) $emailFields[$field['type']] = array();
            $emailFields[$field['type']][] = $results;
            break;
         case 'type (email)':
            if(!$emailFields['type']) $emailFields['type'] = array();
            $emailFields['type'][] = $results;
            break;
         case 'tel':
            if(!$telFields[$field['type']]) $telFields[$field['type']] = array();
            $telFields[$field['type']][] = $results;
            break;
         case 'type (tel)':
            if(!$telFields['type']) $telFields['type'] = array();
            $telFields['type'][] = $results;
            break;
         case 'geo latitude': case 'geo longitude':
            $geoFields[$field['field']] = $results;
            break;
         case 'photo': case 'logo': case 'sound': case 'note':
            foreach ($results as $node) {
               $node->setAttribute('class',$field['field']);
               $newDom = new DOMDocument;
               $newDom->appendChild($newDom->importNode($node,1));
               echo '   '.str_replace("<?xml version=\"1.0\"?>\n",'',$newDom->saveXML());
            }//end foreach results
            break;
         case 'bday':
            $timecode = strtotime($results->nodeValue);
            echo '<abbr title="'.date('c',$timecode).'">'.date('Y-m-d',$timecode).'</abbr>'."\n";
            break;
      }//end switch
   }//end foreach uf

   if($nameFields && count($nameFields)) {
      echo '   <div class="n">';
      foreach($nameFields as $field => $results) {
         foreach($results as $node)
            echo '<span class="'.$field.'">'.htmlentities($node->nodeValue).'</span>';
      }//end foreach
      echo '</div>'."\n";
   }//end if count namefields

   if($emailFields && count($emailFields)) {
      foreach($emailFields['email'] as $id => $results) {
         foreach($results as $node) {
            $mailto = $node->getAttribute('href') ? $node->getAttribute('href') : 'mailto:'.$node->nodeValue;
             if(count($emailFields['type']))
                echo '<span class="email"><span class="type">'.htmlentities($emailFields['type']['id']).'</span> <a class="value" href="'.htmlentities($mailto).'">Email</a></span>'."\n";
             else
                echo '<a class="email" href="'.htmlentities($mailto).'">Email</a>'."\n";
         }//end foreach
      }//end foreach email
   }//end if count emailFields

   if($telFields && count($telFields)) {
      foreach($telFields['tel'] as $id => $results) {
         foreach($results as $node) {
             if(count($telFields['type']))
                echo '<div class="tel"><span class="type">'.htmlentities($telFields['type']['id']).'</span> <span class="value">'.htmlentities($node->nodeValue).'</span></div>';
             else
                echo '<div class="tel">'.htmlentities($node->nodeValue).'</div>';
         }//end foreach
      }//end foreach tel
   }//end if count telFields

   if($geoFields && count($geoFields)) {
      echo '<div class="geo">'."\n";
      echo '   <span class="latitude">'.$geoFields['geo latitude']->item(0)->nodeValue.'</span>'."\n";
      echo '   <span class="longitude">'.$geoFields['geo longitude']->item(0)->nodeValue.'</span>'."\n";
      echo '</div>'."\n";
   }//end if geoFields

   echo '</div>'."\n\n";
}//end function to_hcard

?>