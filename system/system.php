<?php
date_default_timezone_set("Europe/Moscow") ;


$path = dirname(__FILE__);


include_once($path."/ndatabase.php");
include_once($path."/config.php");
$configPath = realpath($path."/../configs/config.ini");
NConfig::setBase($configPath);
include_once($path."/nlog.php");

include_once($path."/models/user.php");
include_once($path."/models/group.php");
include_once($path."/models/method.php");
include_once($path."/models/book.php");
include_once($path."/models/category.php");
include_once($path."/models/blog.php");
include_once($path."/models/comment.php");
include_once($path."/models/message.php");
include_once($path."/models/announce.php");

include_once($path."/models/static_page.php");

include_once($path."/models/page_block.php");
include_once($path."/models/chapter.php");

include_once($path."/abstract_controller.php");
include_once($path."/abstract_view.php");
include_once($path."/core_app.php");
include_once($path."/core_view.php");

include_once($path."/ntemplate.php");

include_once($path."/cache.php");

include_once($path."/qquploadedfile.php");









//system functions

/*
Парсит целое число $enum
забивая его на группы по разрядам,
*/
function enumParser($enum,$d = 0x10){
  $enum = (int)$enum;
  $result = array();
  $f = 1;
  while($enum){
    $tmp = $enum % $d * $f;
    if($tmp){
      $result[] = $tmp;
    }
    $f *= $d;
    $enum = (int)($enum/$d);
  }
  return $result;
}

function enumTranslator($enum,array $map){
  $result = "";

  foreach(enumParser($enum) as $part){
    if(!empty($result)){
      $result .= ",";
    }
    if(isset( $map[$part] )){
      $result .= "'".$map[$part]."'";
    }else{
      $result .= "''";
    }
  }
  return $result;
}
