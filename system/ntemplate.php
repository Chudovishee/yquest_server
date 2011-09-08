<?php

//url sys function 


class NTemplate{


  public $regExpModifier = "";/* use "i" - for case-insensitive parsing or "" for case-sensitive */

  public $cmpDir = "";

  public $cacheExpire = 3600; //sec

  public $file = "";
  
  public $controller = NULL;

  private $html;
  private $define;
  private $tmp;
  private $urls = array();
  private $debug = false;



  public function __construct($file,NConfig $config = NULL){
    
    if( $config && $config->cmpDir && $config->cacheExpire){
      $this->cmpDir = $config->cmpDir;
      $this->cacheExpire = $config->cacheExpire;
      $this->debug = (bool)$config->debug;
       $this->define = $config->define->values();
//    $this->define = array("url"=> "http://life-book.ru/");
  
      if(!is_array($this->define)){
	$this->define = array();
      }
      $this->define["systemUser"] = userModel::systemUser();
    }

    $this->setFile($file);
  }



  public function setCacheExpire($exp){
    $this->cacheExpire = $exp;
    return true;
  }

  private function parseUrlComponents($url,&$vars){
    $result = "array(";
    foreach($url as $key=>$value){
      $result .= "\"".$key."\"=>";
      if(is_array($value)){
	$result .= $this->parseUrlComponents($value,$vars);
      }else{
	$test = preg_replace(
	  '/\{([\w\.\-><\'\(\)&|\^!=\+\*\/ ]+)\}/e'.$this->regExpModifier,
	  "\$this->parseVar('$1',false)", $value);

	if($test != $value){
	  $var  = "\$u_".md5($value);
	  array_push($vars,$var."=\"".$test."\";");
	  $result .= "&".$var;
	}else{
	  $result .= "\"".$value."\"";
	}
	
      }
      $result .= ",";
    }
    $result .= ")";

    return $result;

  }


  private function parseUrl($url){
    //if($this->controller){
      $key = md5($url);
      if(isset($this->urls[$key])){
	
      }else{
	parse_str ( $url, $out );
	$vars = array();
	//var_dump();
	//var_dump(array_unique($vars));
	$this->tmp .= "<?php 
	  \$restore = \$this->controller->userQuery();
	  \$this->controller->userQuery(false,array_merge(\$this->controller->userQuery(),".$this->parseUrlComponents($out,$vars)."));
	  \$this->controller->resetUserQuery();
	  \$urls[\"$key\"] = \$this->controller->query();
	  \$this->controller->userQuery(false,\$restore);
		      ?>";
	$this->urls[$key] =  "<?php
	  ".implode("\n",array_unique($vars))."
	  echo  \$data[\"url\"].\"?\".http_build_query(\$urls[\"$key\"]);
		?>";
      }
      return $this->urls[$key];

    //}
    return false;
  }
//   private function url($url){
//     parse_str ( $url, $out );
//     //var_dump($out);
//     $tmp = $this->mergeUrlPoint;
//     $this->mergeUrlPoint = array_merge($this->mergeUrlPoint,$out);
//     $result = http_build_query($this->baseUrl) ;
//     $this->mergeUrlPoint = $tmp;
//     return $result;
//   }

  private function parseVarExpression($exp){
    $result = false;

    $result = preg_replace(
      '/(\.|\->)([A-Za-z_][\w]*(?:\(\))?)|\.([0-9]+|(?:\'.*?\'))|([A-Za-z_][\w]*(?:\(\))?)|([0-9]+|(?:\'.*?\'))/e'.$this->regExpModifier,
      "\$this->parseVarName(array(\"$0\",'$1','$2','$3','$4','$5'))", $exp);

  
    return $result;
  }
  private function parseVarName($set){
    
   /* //$var = explode(".",$var);
    $result = false;

      preg_match_all(
    "/(\.|\->)([A-Za-z_][\w\(\)]*)|\.([0-9]+)|([A-Za-z_][\w\(\)]*)|([0-9]+)/".$this->regExpModifier,
    $var, $parts, PREG_SET_ORDER);
    foreach ($parts as $set) {
      if($set[1] == "->"){ 
	$result .= $set[1].$set[2];
      }elseif($set[1] == "."){
	$result .= "[\"".$set[2]."\"]";
      }elseif($set[3]){
	$result .= "[".$set[3]."]";
      }elseif($set[4]){
	$result .= "\$data[\"".$set[4]."\"]";
      }else{
	$result .= $set[5];
      }
    }
  
    return $result;*/
    $result = "";
    if($set[1] == "->"){ 
      $result .= $set[1].$set[2];
    }elseif($set[1] == "."){
      $result .= "[\"".$set[2]."\"]";
    }elseif($set[3]){
      $result .= "[".$set[3]."]";
    }elseif($set[4]){
      $result .= "\$data[\"".$set[4]."\"]";
    }else{
      $result .= $set[5];
    }
    return $result;
  }
  
  private function parseVar($tag,$echo = true){
    $mnem = array(
      "!amp",
      "!plus"
    );
    $replace = array(
      "&",
      "+"
    );
    $tag = str_replace($mnem,$replace,$tag);
    $tag = $this->parseVarExpression($tag);
    if($echo)
      return "<?php echo ($tag); ?>";
    else
      return "\".$tag.\"";
  }




  private function &parseEach($tag,$type) {
    $result = "";
    switch($type){
      case "open":
	$tag = explode(" ",trim($tag)); // n = " var[ key[ value]]"
	//если key value не заданно, то по умолчанию используются key value, var - это имя переменной с данными
	
	if(isset($tag[0])){
	  $var = $this->parseVarExpression($tag[0]);
	  $key = isset($tag[1])?$tag[1]:"key";
	  $value = isset($tag[2])?$tag[2]:"value";
	  $values = "\$v_".md5($var);
	  $count = "\$c_".md5($var);
	  $position = "\$p_".md5($var);
	  $result .= "<?php
	    $values = &$var;
	    if ((is_array($values) && ($count = count($values))) || ( ($values instanceof NDataBaseQuery) && ($count = ".$values."->count()))){
	      for($position=0; $position < $count;$position++){
		if(is_array($var)){
		  list(\$data[\"$key\"],\$data[\"$value\"]) = each($values);
		}else{
		  \$data[\"$key\"] = $position;
		  \$data[\"$value\"] = ".$values."->nextInterface();
		}
		      ?>";
	}
      break;
      case "else":
	$result .= "<?php }}else{{ ?>";
      break;
      case "close":
	$result .= "<?php }} ?>";
      break;

    }

  return $result;
  }
  private function &parseIf($tag,$type) {
    $result = "";
    switch($type){
      case "open":
	$var = $this->parseVarExpression(trim($tag));
	$result .= "<?php if ( $var){ ?>";
      break;
      case "elseif":
	$var = $this->parseVarExpression(trim($tag));
	$result .= "<?php }elseif ( $var){ ?>";
      break;
      case "else":
	$result .= "<?php }else{ ?>";
      break;
      case "close":
	$result .= "<?php } ?>";
      break;

    }

  return $result;
  }
  
  
  /** public function readTemplate($tmpl_name)
  *   @param $tmpl_name: 
  *   @return:
  */
  private function &readTemplate(){

    $template = "";
    $cache = NCache::getCache();
    $cacheKey = "templates_".$this->file;
    $template = @$cache->get($cacheKey);
    
    if(!$template){
      //echo $cacheKey."<br/>"; 
      //echo "file cache miss<br/>";

      if($fh = fopen($this->file, 'r')){
	$template = fread($fh, filesize($this->file));
	fclose($fh);
	@$cache->set ( $cacheKey, $template , $this->cacheExpire ,array("template"));
      }
    }//else{
      //echo $cacheKey."<br/>"; 
      //echo "file cache hit<br/>";
    //}

    return $template;
  }



  private function &parseTemplate(&$tmpl){

    //$result = "";

//comments
	$tmpl = preg_replace(
	  '/\/\*(.*?)\*\//s'.$this->regExpModifier,
	  "", $tmpl);

      	//urls
	$tmpl= preg_replace(
	  '/\{url\}(.*?)\{\/url\}/e'.$this->regExpModifier,
	  "\$this->parseUrl('$1')", $tmpl);



	$tmpl= preg_replace(
	  '/\{each ([\w\.\-><\'\(\)&|\^!=\+\*\/ ]+(?: [A-Za-z_][\w]*? [A-Za-z_][\w]*?)?)\}/e'.$this->regExpModifier,
	  "\$this->parseEach('$1','open')", $tmpl);
	$tmpl= preg_replace(
	  '/\{eachelse\}/e'.$this->regExpModifier,
	  "\$this->parseEach('','else')", $tmpl);
	$tmpl= preg_replace(
	  '/\{\/each\}/e'.$this->regExpModifier,
	  "\$this->parseEach('','close')", $tmpl);

//if
	$tmpl= preg_replace(
	  '/\{if ([\w\.\-><\'\(\)&|\^!=\+\*\/ ]+)\}/e'.$this->regExpModifier,
	  "\$this->parseIf('$1','open')", $tmpl);
	$tmpl= preg_replace(
	  '/\{elseif ([\w\.\-><\'\(\)&|\^!=\+\*\/ ]+)\}/e'.$this->regExpModifier,
	  "\$this->parseIf('$1','elseif')", $tmpl);
	$tmpl= preg_replace(
	  '/\{else\}/e'.$this->regExpModifier,
	  "\$this->parseIf('','else')", $tmpl);
  	$tmpl= preg_replace(
	  '/\{\/if\}/e'.$this->regExpModifier,
	  "\$this->parseIf('','close')", $tmpl);

	//tags
	$tmpl = preg_replace(
	  '/\{([\w\.\-><\'\(\)&|\^!=\+\*\/ ]+)\}/e'.$this->regExpModifier,
	  "\$this->parseVar('$1')", $tmpl);


    return $tmpl;
  }



  public function &html(&$hash){

    $cache = NCache::getCache();
    $cacheKey = "templates_".md5(var_export($hash, true)).$this->file;
    $cmpFile = $this->cmpDir.md5($this->file).".cmp";

    $this->html = @$cache->get($cacheKey);

    if(!$this->html){
      if($this->define){
	$data = array_merge($this->define,$hash);
      }else{
	$data = $hash;
      }
      
      if(!$this->debug && file_exists($cmpFile)){
	//echo 'file cache hit!';
	include($cmpFile);;
      }else{
	//echo 'file cache miss!';
      
	$this->tmp  = "<?php ob_start();?>";
	$this->tmp .= $this->parseTemplate($this->readTemplate());
	$this->tmp .= "<?php \$this->html = ob_get_contents(); ob_end_clean(); ?>";
	
	//$tmp = str_replace("\n"," ",$tmp);
	$this->tmp = preg_replace("/\?\>[\s]*\<\?php/", "", $this->tmp);
	$this->tmp = preg_replace("/\.?\"[\s]*\"\.?/", "", $this->tmp);
	
	if($fh = fopen($cmpFile, 'w')){
	  $template = fwrite($fh, $this->tmp);
	  fclose($fh);
	  include($cmpFile);
	}
      }
      @$cache->set ( $cacheKey,$this->html , $this->cacheExpire ,array("template"));
      //echo $cacheKey."<br/>"; 
      //echo "html cache miss!<br/>";
    }//else{
	//echo $cacheKey."<br/>"; 
       //echo "html cache hit!<br/>";
    //}
    return $this->html;

  }


  public function setFile($file){
    $this->file = $file;
  }




}