<?php

function std_rss_out($data) {

$rtrn = '';

$rtrn .= '<?xml version="1.0" ?>'."\n";
$rtrn .= '<rss version="2.0" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/">'."\n";
$rtrn .= '   <channel>'."\n";
$rtrn .= '      <title>'.htmlspecialchars($data['title']).'</title>'."\n";
$rtrn .= '      <link>'.htmlspecialchars($data['link']).'</link>'."\n";
$rtrn .= '      <description>'.htmlspecialchars($data['description']).'</description>'."\n";
if($data['image'])
   $rtrn .= '      <image>'."\n";
if($data['image']['link'])
   $rtrn .= '         <link>'.htmlspecialchars($data['image']['link']).'</link>'."\n";
if($data['image']['url'])
   $rtrn .= '         <url>'.htmlspecialchars($data['image']['url']).'</url>'."\n";
if($data['image']['title'])
   $rtrn .= '         <title>'.htmlspecialchars($data['image']['title']).'</title>'."\n";
if($data['image'])
   $rtrn .= '      </image>'."\n";
if($data['language'])
   $rtrn .= '      <language>'.htmlspecialchars($data['language']).'</language>'."\n";
if($data['copyright'])
   $rtrn .= '      <copyright>'.htmlspecialchars($data['copyright']).'</copyright>'."\n";
if($data['webMaster'])
   $rtrn .= '      <webMaster>'.htmlspecialchars($data['webMaster']).'</webMaster>'."\n";
if($data['dc:creator'])
   $rtrn .= '      <dc:creator>'.htmlspecialchars($data['dc:creator']).'</dc:creator>'."\n";
if($data['pubDate'])
   $rtrn .= '      <pubDate>'.htmlspecialchars(date('r',$data['pubDate'])).'</pubDate>'."\n";
if($data['category']) {
   if(is_array($data['category'])) {
      foreach($data['category'] as $cat)
         $rtrn .= '      <category>'.htmlspecialchars($cat).'</category>'."\n";
   } else
      $rtrn .= '      <category>'.htmlspecialchars($data['category']).'</category>'."\n";
}//end if category

foreach($data['items'] as $id => $item) {
   $rtrn .= '      <item>'."\n";
   $rtrn .= '         <title>'.htmlspecialchars($item['title']).'</title>'."\n";
   if($item['link'])
      $rtrn .= '         <link>'.htmlspecialchars($item['link']).'</link>'."\n";
   if(!$item['guid']) $item['guid'] = md5($item['title'].$item['description']);
   if(preg_match('/^[^ ]+\.[a-z]{2,4}(\/[^ ]*)?$/',$item['guid']))
      $rtrn .= '         <guid>'.htmlspecialchars($item['guid']).'</guid>'."\n";
   else
      $rtrn .= '         <guid isPermaLink="false">'.htmlspecialchars($item['guid']).'</guid>'."\n";
   if($item['description'])
      $rtrn .= '         <description>'.htmlspecialchars($item['description']).'</description>'."\n";
   if($item['dc:creator'])
      $rtrn .= '         <dc:creator>'.htmlspecialchars($item['dc:creator']).'</dc:creator>'."\n";
   if($item['author'])
      $rtrn .= '         <author>'.htmlspecialchars($item['author']).'</author>'."\n";
   if($item['comments'])
      $rtrn .= '         <comments>'.htmlspecialchars($item['comments']).'</comments>'."\n";
   if($item['enclosure'])
      $rtrn .= '         <enclosure>'.htmlspecialchars($item['enclosure']).'</enclosure>'."\n";
   if($item['pubDate'])
      $rtrn .= '         <pubDate>'.htmlspecialchars(date('r',$item['pubDate'])).'</pubDate>'."\n";
   if($item['source'])
      $rtrn .= '         <source>'.htmlspecialchars($item['source']).'</source>'."\n";
   if($item['wfw:comment'])
      $rtrn .= '         <wfw:comment>'.htmlspecialchars($item['wfw:comment']).'</wfw:comment>'."\n";
   if($item['wfw:commentRss'])
      $rtrn .= '         <wfw:commentRss>'.htmlspecialchars($item['wfw:commentRss']).'</wfw:commentRss>'."\n";

   if($item['category']) {
      if(is_array($item['category'])) {
         foreach($item['category'] as $cat)
            $rtrn .= '         <category>'.htmlspecialchars($cat).'</category>'."\n";
      } else
         $rtrn .= '         <category>'.htmlspecialchars($item['category']).'</category>'."\n";
   }//end if category

   $rtrn .= '      </item>'."\n";
}//end foreach items

$rtrn .= '   </channel>'."\n";
$rtrn .= '</rss>';

return $rtrn;

}//end function std_rss_out

?>