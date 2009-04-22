<?php

class Outline {
   var $_fields = array();
   var $_subnodes = array();
   var $_constructed = false;
   
   function Outline($data='',$options=array()) {
      if($_constructed) return;
      $this->__construct($data,$options);
      $this->_constructed = true;
   }//end PHP4 constructor
   
   function __construct($data='',$options=array()) {
      if($_constructed) return;
      if(is_array($data)) {
         foreach($data as $id => $el) {
            if(is_array($el))
               $el = new Outline($el);
            if(is_numeric($id))
               $this->addNode($el);
            else
               $this->addField($id,$el);
         }//end foreach
      }//end if is_array data
      if(is_a($data,'Outline')) {
         $this->_fields = $data->getFields();
         $this->_subnodes = $data->getNodes();
      }//end if is_a Outline
      if(!is_array($data) && !is_a($data,'Outline') && $data)
         $this->addField('text',$data);
      $this->_constructed = true;
   }//end constructor
   
   //reassign new indexes from zero to all subnodes
   function reindexNodes() { $this->_subnodes = array_values($this->_subnodes); }
   
   //data accessors/mutators
   function addNode($data='') { $this->_subnodes[] = new Outline($data); }
   function setNode($index,$data='') { $this->_subnodes[$index] = new Outline($data); }
   function unsetNode($index) { unset($this->_subnodes[$index]); }
   function getNode($index) { return $this->_subnodes[$index]; }
   
   function unsetAllNodes() { $this->_subnodes = array(); }
   function getNumNodes() { return count($this->_subnodes); }
   function getNodes() { return $this->_subnodes; }
   
   function addField($name,$data='') { $this->_fields[$name] = $data; }
   function setField($name,$data) { $this->_fields[$name] = $data; }
   function unsetField($name) { unset($this->_fields[$name]); }
   function getField($name) { return $this->_fields[$name]; }
   
   function getFields() { return $this->_fields; }
   
   //tranformation functions
   function fieldsFromFirstNode($ignorefields=array('text')) {//take the fields from the first subnode and assign them to us -- if there are no subnodes on the first node, remove it
      $node =& $this->getNode(0);
      $fields = $node->getFields();
      foreach($fields as $name => $val) {
         if(!in_array($name,$ignorefields)) {
            $node->unsetField($name);
            $this->addField($name,$val);
         }//end if name !=
      }//end foreach fields
      if(!$node->getNumNodes() && !count($node->getFields())) {
         $this->unsetNode(0);
         $this->reindexNodes();
      }//end if ! count node->getNodes
   }//end function fieldsFromFirstNode

   function nodesFromField($field='',$recursive=false) {
      $passedfield = $field;
      if(!$field) {
         foreach($this->getFields() as $name => $val) {
            if(is_a($val,'Outline') && $val->getNumNodes()) {
               $field = $name;
               break;
            }//end if is_a Outline
        }//end foreach fields
      }//end if ! itemel
      if($field) {
         $nodes = $this->getField($field);
         if(is_a($nodes,'Outline') && $nodes->getNumNodes()) {
            foreach($nodes->getFields() as $name => $val)
               $this->addField($name,$val);
            foreach($nodes->getNodes() as $node) {
               if($recursive)
                  $node->nodesFromField($passedfield,true);
               $this->addNode($node);
            }//end foreach
            $this->unsetField($field);
         } else {
            if($nodes)
               $this->addNode($nodes);
            $this->unsetField($field);
         }//end if-else is_a nodes Outline
      }//end if itemel
      if(!$field && $recursive) {
         for($count=0; $count < $this->getNumNodes(); $count++)
            $this->_subnodes[$count]->nodesFromField($passedfield,true);
      }//end if ! filed && recursive
   }//end function nodesFromField
   
   function fillURLs() {
      if($this->_fields['rssurl']) {$this->_fields['xmlUrl'] = $this->_fields['rssurl'];unset($this->_fields['rssurl']);}
      if($this->_fields['rssUrl']) {$this->_fields['xmlUrl'] = $this->_fields['rssUrl'];unset($this->_fields['rssUrl']);}
      if($this->_fields['htmlurl']) {$this->_fields['htmlUrl'] = $this->_fields['htmlurl'];unset($this->_fields['htmlurl']);}
      if($this->_fields['xmlurl']) {$this->_fields['xmlUrl'] = $this->_fields['xmlurl'];unset($this->_fields['xmlurl']);}
      if($this->_fields['href'] && !$this->_fields['href#1']) {$this->_fields['href#1'] = $this->getAlternateURL($this->_fields['href']);}
      if($this->_fields['htmlUrl'] && !$this->_fields['xmlUrl']) {$this->_fields['xmlUrl'] = $this->_fields['rssUrl'] ? $this->_fields['rssUrl'] : $this->getAlternateURL($this->_fields['htmlUrl']);}
      if($this->_fields['xmlUrl'] && !$this->_fields['htmlUrl']) {$this->_fields['htmlUrl'] = $this->getAlternateURL($this->_fields['xmlUrl']);}
      for($count=0; $count < $this->getNumNodes(); $count++)
         $this->_subnodes[$count]->fillURLs();
   }//end function fillURLs
   
   function simplify($allowfields=array('contents','href','title','rel','rev','class','text','type','xmlurl','htmlurl','rssurl','datecreated','ownername'),$allowalts=true) {
      foreach($this->_fields as $name => $val) {
         $keepit = in_array(strtolower($name),$allowfields);
         if(!$keepit && $allowalts) {
            $name2 = explode('#',$name);
            $name2 = $name2[0];
            $keepit = in_array(strtolower($name2),$allowfields);
         }//end if allowalts
         if(!$keepit)
            unset($this->_fields[$name]);
      }//end foreach
      for($count=0; $count < $this->getNumNodes(); $count++)
         $this->_subnodes[$count]->simplify();
   }//end function simplify
   
   function toXOXOfields() {
      if(is_a($this->_fields['channel'],'Outline')) {
         $channel = $this->_fields['channel'];
         foreach($channel->getFields() as $name => $val)
            $this->addField($name,$val);
         $this->unsetField('channel');
      }//end if channel
      if(!$this->_fields['href']) {
         foreach($this->getNodes() as $id => $node) {
            if($node->getField('rel') == 'alternate') {
               $this->_fields['href'] = $node->getField('href');
               $this->unsetNode($id);
               break;
            }//end if
         }//end foreach nodes
      }//end if ! href
      if(!$this->_fields['href'] && $this->_fields['link']) {$this->_fields['href'] = $this->_fields['link'];unset($this->_fields['link']);}
      if(!$this->_fields['body'] && $this->_fields['description']) {$this->_fields['body'] = $this->_fields['description'];unset($this->_fields['description']);}
      if(!$this->_fields['body'] && $this->_fields['content']) {$this->_fields['body'] = $this->_fields['content'];unset($this->_fields['content']);}
      if($this->_fields['datecreated']) {$this->_fields['dateCreated'] = $this->_fields['datecreated'];}
      if(!$this->_fields['dateCreated'] && $this->_fields['pubDate']) {$this->_fields['dateCreated'] = $this->_fields['pubDate'];unset($this->_fields['pubDate']);}
      if(!$this->_fields['dateCreated'] && $this->_fields['pubdate']) {$this->_fields['dateCreated'] = $this->_fields['pubdate'];unset($this->_fields['pubdate']);}
      if(!$this->_fields['dateCreated'] && $this->_fields['modified']) {$this->_fields['dateCreated'] = date('r',strtotime($this->_fields['modified']));unset($this->_fields['modified']);}
      if($this->_fields['ownername']) {$this->_fields['ownerName'] = $this->_fields['ownername'];}
      if(!$this->_fields['ownerName'] && $this->_fields['webmaster']) {$this->_fields['ownerName'] = $this->_fields['webmaster'];unset($this->_fields['webmaster']);}
      if(!$this->_fields['ownerName'] && $this->_fields['webMaster']) {$this->_fields['ownerName'] = $this->_fields['webMaster'];unset($this->_fields['webMaster']);}
      if($this->_fields['htmlUrl']) {$this->_fields['href'] = $this->_fields['htmlUrl'];}
      if($this->_fields['htmlurl']) {$this->_fields['href'] = $this->_fields['htmlurl'];}
      if($this->_fields['xmlUrl']) {$this->_fields['href#1'] = $this->_fields['xmlUrl'];$this->_fields['rel#1'] = 'alternate '.$this->_fields['type'];$this->_fields['text#1'] = '[feed]';}
      if($this->_fields['xmlurl']) {$this->_fields['href#1'] = $this->_fields['xmlurl'];$this->_fields['rel#1'] = 'alternate '.$this->_fields['type'];$this->_fields['text#1'] = '[feed]';}
      if($this->_fields['rssUrl']) {$this->_fields['href#1'] = $this->_fields['rssUrl'];$this->_fields['rel#1'] = 'alternate '.$this->_fields['type'];$this->_fields['text#1'] = '[feed]';}
      if($this->_fields['rssurl']) {$this->_fields['href#1'] = $this->_fields['rssurl'];$this->_fields['rel#1'] = 'alternate '.$this->_fields['type'];$this->_fields['text#1'] = '[feed]';}
      if(!$this->_fields['text'] && $this->_fields['title']) {$this->_fields['text'] = $this->_fields['title'];}
      unset($this->_fields['htmlUrl']);
      unset($this->_fields['xmlUrl']);
      unset($this->_fields['htmlurl']);
      unset($this->_fields['xmlurl']);
      unset($this->_fields['rssUrl']);
      unset($this->_fields['rssurl']);
      unset($this->_fields['type']);
      unset($this->_fields['datecreated']);
      unset($this->_fields['ownername']);
      for($count=0; $count < $this->getNumNodes(); $count++)
         $this->_subnodes[$count]->toXOXOfields();
   }//end function toXOXOfields
   
   function toOPMLfields() {
      if(is_a($this->_fields['channel'],'Outline')) {
         $channel = $this->_fields['channel'];
         foreach($channel->getFields() as $name => $val)
            $this->addField($name,$val);
         $this->unsetField('channel');
      }//end if channel
      if($this->_fields['rel'] == 'alternate' || $this->_fields['rel'] == 'rss' || $this->_fields['rel'] == 'atom') {
         if($this->_fields['rel'] != 'alternate') {$this->_fields['type'] = $this->_fields['rel'];}
         if($this->_fields['href']) {$this->_fields['xmlUrl'] = $this->_fields['href'];}
         if($this->_fields['href#1']) {$this->_fields['htmlUrl'] = $this->_fields['href#1'];}
      } else {
         $isjs = explode(':',$this->_fields['href']);
         $isjs = ($isjs[0] == 'javascript');
         if($this->_fields['href'] && !$isjs)
            $this->_fields['htmlUrl'] = $this->_fields['href'];
         else if($this->_fields['href#1']) {
            $this->_fields['htmlUrl'] = $this->_fields['href#1'];
            $this->_fields['text'] = $this->_fields['text#1'];
         }//end if-else href && !isjs
         if($this->_fields['href#1'] && !$isjs) 
            $this->_fields['xmlUrl'] = $this->_fields['href#1'];
         else if($this->_fields['href#2'])
            $this->_fields['xmlUrl'] = $this->_fields['href#2'];
      }//end if-else rels
      if(!$this->_fields['dateCreated'] && $this->_fields['datecreated']) {$this->_fields['dateCreated'] = $this->_fields['datecreated'];unset($this->_fields['datecreated']);}
      if(!$this->_fields['dateCreated'] && $this->_fields['pubDate']) {$this->_fields['dateCreated'] = $this->_fields['pubDate'];unset($this->_fields['pubDate']);}
      if(!$this->_fields['dateCreated'] && $this->_fields['pubdate']) {$this->_fields['dateCreated'] = $this->_fields['pubdate'];unset($this->_fields['pubdate']);}
      if(!$this->_fields['dateCreated'] && $this->_fields['modified']) {$this->_fields['dateCreated'] = date('r',strtotime($this->_fields['modified']));unset($this->_fields['modified']);}
      if(!$this->_fields['ownerName'] && $this->_fields['ownername']) {$this->_fields['ownerName'] = $this->_fields['ownername'];unset($this->_fields['ownername']);}
      if(!$this->_fields['ownerName'] && $this->_fields['webmaster']) {$this->_fields['ownerName'] = $this->_fields['webmaster'];unset($this->_fields['webmaster']);}
      if(!$this->_fields['ownerName'] && $this->_fields['webMaster']) {$this->_fields['ownerName'] = $this->_fields['webMaster'];unset($this->_fields['webMaster']);}
      if(!$this->_fields['htmlUrl'] && $this->_fields['link']) {$this->_fields['htmlUrl'] = $this->_fields['link'];unset($this->_fields['link']);}
      if(!$this->_fields['text'] && $this->_fields['description']) {$this->_fields['text'] = $this->_fields['description'];unset($this->_fields['description']);}
      if(!$this->_fields['text'] && $this->_fields['content']) {$this->_fields['text'] = $this->_fields['content'];unset($this->_fields['content']);}
      if(!$this->_fields['title'] && $this->_fields['text']) {$this->_fields['title'] = $this->_fields['text'];}
      if(!$this->_fields['text'] && $this->_fields['title'] && !($this->_fields['dateCreated'] || $this->_fields['ownerName'])) {$this->_fields['text'] = $this->_fields['title'];}
      if(!$this->_fields['htmlUrl']) {
         foreach($this->getNodes() as $id => $node) {
            if($node->getField('rel') == 'alternate') {
               $this->_fields['htmlUrl'] = $node->getField('href');
               $this->unsetNode($id);
               break;
            }//end if
         }//end foreach nodes
      }//end if ! href
      unset($this->_fields['rel']);
      unset($this->_fields['href']);
      unset($this->_fields['text#1']);
      unset($this->_fields['href#1']);
      unset($this->_fields['rel#1']);
      unset($this->_fields['text#2']);
      unset($this->_fields['href#2']);
      unset($this->_fields['rel#2']);
      for($count=0; $count < $this->getNumNodes(); $count++)
         $this->_subnodes[$count]->toOPMLfields();
   }//end function toOPMLfields
   
   //to-whatever functions
   function toArray() {
      $nodes = array();
      $fields = array();
      foreach($this->getNodes() as $node)
         $nodes[] = $node->toArray();
      foreach($this->getFields() as $name => $val) {
         if(is_a($val,'Outline'))
            $fields[$name] = $val->toArray();
         else
            $fields[$name] = $val;
      }//end foreach fields
      return array_merge($fields,$nodes);
   }//end function toArray

   function toXOXO($classes=array('xoxo'),$fields2firstelem=true,$itemclass=array()) {
      $returnval = '';
      if($classes != 'item') {
         $classtring = (count($classes) > 0)?' class="'.implode(' ',$classes).'"':'';
         $returnval .= '<ul'.$classtring.'>'."\n";//start xoxo section
         if($fields2firstelem && (count($this->getFields()) > 0)) {
            $returnval .= '   <li>';//start item
            $returnval .= $this->fields2xoxo();
            $returnval .= "</li>\n";//end item
         }//end if fields2firstelem
      } else {
         $itemclasstr = count($itemclass) ? ' class="'.implode(' ',$itemclass).'"' : '';
         $returnval .= '   <li'.$itemclasstr.'>';//start xoxo item
         $returnval .= $this->fields2xoxo();
         if($this->getNumNodes())
            $returnval .= "\n      <ol>\n";
      }//end if-else classes != item
      for($count=0; $count < $this->getNumNodes(); $count++) {
         $tmp = $this->getNode($count);
         $returnval .= $tmp->toXOXO('item',false,$itemclass);
      }//end for getNumNodes
      if($classes != 'item')
         $returnval .= '</ul>';//end xoxo section
      else {
         if($this->getNumNodes())
            $returnval .= "      </ol>\n   ";
         $returnval .= "</li>\n";//end xoxo item
      }//end if-else classes != item
      return $returnval;
   }//end function toXOXO

   function toOPML($item=false) {
      $returnval = '';
      if(!$item) {
         $returnval .= '<?xml version="1.0" ?>'."\n";//XML header
         $returnval .= '<opml version="1.0"';//start opml section";
         foreach($this->getFields() as $name => $val) {
            $tmp = explode(':',$name);
            if($tmp[0] == 'xmlns')
               $returnval .= ' '.$name.'="'.htmlspecialchars($val).'"';
         }//end foreach getFields
         $returnval .=  '>'."\n";
         $returnval .= '   <head>'."\n";//start head section
         foreach($this->getFields() as $name => $val) {
            $tmp = explode(':',$name);
            if($tmp[0] == 'xmlns') continue;
            if(is_a($val,'Outline')) continue;//multi-value fields are not allowed in OPML <head>
            $returnval .= '      <'.str_replace('>','_',str_replace('#','_',$name)).'>'.htmlspecialchars($val).'</'.str_replace('>','_',str_replace('#','_',$name)).'>'."\n";
         }//end foreach
         $returnval .= '   </head>'."\n";//start head section
         $returnval .= "   <body>\n";
         foreach($this->getNodes() as $node)
            $returnval .= $node->toOPML(true);
         $returnval .= "   </body>\n</opml>";
      } else {
         $returnval .= '      <outline';
         foreach($this->getFields() as $name => $val) {
            if(is_a($val,'Outline')) $val = $val->toJSON($name,true,false);
            $returnval .= ' '.str_replace('>','_',str_replace('#','_',$name)).'="'.htmlspecialchars($val).'"';
         }//end foreach
         if($this->getNumNodes()) {
            $returnval .= ">\n";
            foreach($this->getNodes() as $node)
               $returnval .= $node->toOPML(true);
            $returnval .= "      </outline>\n";
         } else
            $returnval .= " />\n";
      }//end if-else ! item
      return $returnval;
   }//end function toOPML

   function toJSON($nodesl='nodes',$simplenodes=false,$firstel=true) {
      $rtrn = '';
      $count = 0;
      if(count($this->getFields()) || $firstel)
         $rtrn .= '{';
      foreach($this->getFields() as $name => $val) {
         if($count != 0)
            $rtrn .= ',';
         if(is_a($val,'Outline') && $simplenodes && count($val->getFields()) == 1 && $val->getField('text'))
            $val = $val->getField('text');
         if(is_a($val,'Outline'))
            $val = $val->toJSON($nodesl,$simplenodes,false);
         else
            $val = '"'.str_replace("\r",'',str_replace("\n",'\n',addslashes($val))).'"';
         $rtrn .= '"'.addslashes($name).'":'.$val;
         $count++;
      }//end foreach
      if($this->getNumNodes()) {
         if(count($this->getFields()))
            $rtrn .= ',';
         if(count($this->getFields()) || $firstel)
            $rtrn .= '"'.addslashes($nodesl).'":';
         $rtrn .= '[';
         $count = 0;
         foreach($this->getNodes() as $node) {
            if($count != 0)
               $rtrn .= ',';
            if($simplenodes && count($node->getFields()) == 1 && $node->getField('text'))
               $rtrn .= '"'.str_replace("\r",'',str_replace("\n",'\n',addslashes($node->getField('text')))).'"';
            else
               $rtrn .= $node->toJSON($nodesl,$simplenodes,false);
            $count++;
         }//end foreach
         $rtrn .= ']';
      }//end if getNumNodes
      if(count($this->getFields()) || $firstel)
         $rtrn .= '}';
      return $rtrn;
   }//end function toJSON
   
   //private functions
   function getAlternateURL($url) {
      $data = file_get_contents($url);
      $vals = array();
      $theParser = xml_parser_create();
      xml_parse_into_struct($theParser,$data,$vals);
      xml_parser_free($theParser);
      $fallbackhref = '';
      foreach($vals as $el) {
         if($el['tag'] == 'LINK') {
            if(trim($el['value'])) {return trim($el['value']);}
            if(trim($el['attributes']['REL']) == 'alternate') {return trim($el['attributes']['HREF']);}
            $fallbackhref = trim($el['attributes']['HREF']);
         }//end if link
      }//end foreach
      return $fallbackhref;
   }//end function getAlternateURL
   
   function fields2xoxo() {
      $fields = $this->getFields();
      $afields = array('href','title','rel','type','rev','class');
      $atmp = array();
      $ddattmp = array();
      $dltmp = '';
      $rtrn .= '';
      foreach($fields as $id => $val) {//process fields
         if(stristr($id,'#')) {//alternate-a
            $tmp = explode('#',$id);
            $atmp[$tmp[1]][$tmp[0]] = $val;
            continue;
         }//end if stristr id #
         if(in_array($id,$afields)) {//primary a
            $atmp[-1][$id] = $val;//primary a is -1 to make sure it is before all other a's
            continue;
         }//end if id in afields
         if($id == 'text') {//text
            $atmp[-1][$id] = $val;
            continue;
         }//end if id == text
         if(stristr($id,'>>')) {//attributes for dd tag
            $tmp = explode('>>',$id);
            $ddattmp[$tmp[0]][$tmp[1]] = $val;
            continue;
         }//end stristr id >>
         //dl-fields
         $dltmp[$id] = $val;
      }//end foreach fields
      ksort($atmp);
      foreach($atmp as $id => $a) {//process a fields
         if($id == -1 && count($a) < 2) {
            $rtrn .= $a['text'];
            continue;
         }//end if id==-1 && count a < 2
         if($id != -1)
            $rtrn .= "\n      ";
         $rtrn .= '<a';
         foreach($a as $attid => $attval) {
            if($attid == 'text') continue;
            if(is_a($attval,'Outline')) $attval = implode(',',$attval->getFields());
            $rtrn .= ' '.$attid.'="'.htmlspecialchars($attval).'"';
         }//end foreach
         if($this->checkXML('<test>'.$a['text'].'</test>')) //if not well-formed xml
               $a['text'] = htmlspecialchars($a['text']);
         $rtrn .= '>'.$a['text'].'</a>';
      }//end foreach atmp
      if($dltmp) {
         $dltmp2 = '';
         foreach($dltmp as $id => $val) {
            $ddattstr = '';//get attributes for the dd tag (if any)
            if($ddattmp[$id]) {
               foreach($ddattmp[$id] as $attid => $ddatt)
                  $ddattstr .= ' '.$attid.'="'.htmlspecialchars($ddatt).'"';
            }//end if ddatmp[id]
            if(is_array($val))//process array-style field
               $val = new Outline($val);
            if(is_a($val,'Outline'))
               $val = $val->toXOXO();
            if($this->checkXML('<test>'.$val.'</test>')) //if not well-formed xml
               $val = htmlspecialchars($val);
            $dltmp2 .= '      <dt>'.$id.'</dt>'."\n";
            $dltmp2 .= '         <dd'.$ddattstr.'>'.$val.'</dd>'."\n";
         }//end foreach
         if($dltmp2)
            $rtrn .= "\n   <dl>\n".$dltmp2."   </dl>\n   ";
      }//end if dltmp
      return $rtrn;
   }//end function fields2xoxo
   
   function checkXML($data) {//returns FALSE if $data is well-formed XML, errorcode otherwise
      $rtrn = 0;
      $theParser = xml_parser_create();
      if(!xml_parse_into_struct($theParser,$data,$vals)) {
         $errorcode = xml_get_error_code($theParser);
         if($errorcode != XML_ERROR_NONE && $errorcode != 27)
            $rtrn = $errorcode;
      }//end if ! parse
      xml_parser_free($theParser);
      return $rtrn;
   }//end function checkXML
   
}//end class Outline

?>