<?php

class NCache extends Memcache{

  public function __construct(){
    $this->log = NLog::addByConfig( NConfig::getConfig("base/log"), "cache" );
    //parent::__construct();
  }

  public function __destruct(){
    
  }

  //const noneDebugLevel 	   = 0; //ничего
  //const standartDebugLevel = 1; // влог только вызовы
  //const proDebugLevel      = 2;//в лог все запросы с данными

  private static $cache = NULL;
  private $log = NULL;
  private $tagsExpiration = 3600; //1 час
  //private $getMiss = false;

  //private $error_log	= "";		// read write
  //private $debugLevel = self::standartDebugLevel;	// read write

  //private $log_file = false;


  static function getCache(){
    if(!self::$cache){
      self::$cache = new NCache;
    }

    return self::$cache;
  }



  public function getTagsExpiration(){
    return $this->tagsExpiration;
  }
  public function setTagsExpiration($expiration){
    return $this->tagsExpiration = $expiration;
  }
    /* debug lbl geter and seter */
  //public function setDebugLevel($lvl){
  //  return $this->debugLevel = $lvl;
  //}
  //public function debugLevel(){
  //  return $this->debugLevel;
  //}
  /* log file geter and seter */
  //public function setLogFileName($filename){
  //  return $this->error_log = $filename;
  //}
 // public function logFileName(){
 //   return $this->error_log;
  //}

  public function addServers($servers){
    $result = true;
    if($servers instanceof NConfig){
      foreach($servers as $server){
	$server = explode(":",$server); 
	if(!$this->addServer($server[0],$server[1])){
	  $result= false;
	  break;
	}
      }
    }else{
      $result = false;
    }
    return $result;
  }


  public function loadConfig(NConfig $config){
    
    $result = $this->setTagsExpiration($config->tagsExpiration) &&
	      //$this->setDebugLevel($config->debug) &&
	      //$this->setLogFileName($config->log) &&
	      $this->addServers($config->servers);


    //if($this->debugLevel >= self::standartDebugLevel){
      $msg  = "  DEBUG BEGIN\n";
      $msg .= "  FUNCTION CALLED:       loadConfig\n";
      $msg .= "  FUNCTION STATUS:       ".($result?"OK":"FAULT")."\n";
      $msg .= "  TAGS EXPIRATION (SEC): ".$this->getTagsExpiration()."\n";
      //$msg .= "  LOG FILE:              ".$this->logFileName()."\n";
      //$msg .= "  DEBUG LEVEL:           ".$this->debugLevel()."\n";
      $msg .= "  SERVERS:               ";
      foreach($config->servers as $server){
	$msg .= $server."\n                         ";
      }
      $msg .= "\n";
      $msg .= "  DEBUG END\n";
      if($this->log)$this->log->write($msg,NLog::std);
    //}
    
    return $result;

  }



  private function &setTagHelper(array &$tags){
    $result = false;

    $time = round(microtime(true)*1000);

    if(count($tags)){
      $result = array();
      //$newTags = array();

      $tags_values = parent::get($tags);
      
      foreach($tags as $tag){
	if(isset($tags_values[$tag])){
	  $result[$tag] = $tags_values[$tag];
	}
	else{
	  $result[$tag] = $time;
	  $addTagResult = parent::add($tag,$time,false,$this->tagsExpiration);
	  
	  //if($this->debugLevel >= self::standartDebugLevel){
	    $msg  = "  DEBUG BEGIN\n";
	    $msg .= "  FUNCTION CALLED:       setTagHelper (add new tag)\n";
	    $msg .= "  FUNCTION STATUS:       ".($addTagResult?"OK":"FAULT")."\n";
	    $msg .= "  TAG:                   ".$tag."\n";
	    $msg .= "  DEBUG END\n";
	   if($this->log)$this->log->write($msg,NLog::std);
	 // }
	}
      }
    }
    return $result;

  }


  public function add($key ,$value , $expiration = 0 ,array $tags = array()){
    $cacheObj = array();
    $cacheObj["value"] = $value;
    $cacheObj["tags"] = &$this->setTagHelper($tags);
    
    $result = parent::add($key,$cacheObj,false,$expiration);

    //if($this->debugLevel >= self::standartDebugLevel){
      $msg  = "  DEBUG BEGIN\n";
      $msg .= "  FUNCTION CALLED:       add\n";
      $msg .= "  FUNCTION STATUS:       ".($result?"OK":"FAULT")."\n";
      $msg .= "  KEY:                   ".$key."\n";
      $msg .= "  TAGS:                  ";
      foreach($tags as $tag){
	$msg .= $tag." ";
      }
      $msg .= "\n";
      $msg .= "  EXPIRATION:            ".$expiration."\n";
      if($this->log->level() == NLog::debug){
	$msg .= "  VALUE:                  \n";
	$msg .= "  /*    VAR EXPORT BEGIN    */\n";
	$msg .= var_export($value,true)."\n";
	$msg .= "  /*     VAR EXPORT END     */\n";
      }
      $msg .= "  DEBUG END\n";
      if($this->log)$this->log->write($msg,NLog::std);
    //}
    return $result;
  }


  public function set( $key ,$value , $expiration = 0, array $tags = array()){
    $cacheObj = array();
    $cacheObj["value"] = $value;
    $cacheObj["tags"] = &$this->setTagHelper($tags);
  
    $result = parent::set($key,$cacheObj,false,$expiration);

    //if($this->debugLevel >= self::standartDebugLevel){
      $msg  = "  DEBUG BEGIN\n";
      $msg .= "  FUNCTION CALLED:       set\n";
      $msg .= "  FUNCTION STATUS:       ".($result?"OK":"FAULT")."\n";
      $msg .= "  KEY:                   ".$key."\n";
      $msg .= "  TAGS:                  ";
      foreach($tags as $tag){
	$msg .= $tag." ";
      }
      $msg .= "\n";
      $msg .= "  EXPIRATION:            ".$expiration."\n";
      if($this->log->level() == NLog::debug){
	$msg .= "  VALUE:                  \n";
	$msg .= "  /*    VAR EXPORT BEGIN    */\n";
	$msg .= var_export($value,true)."\n";
	$msg .= "  /*     VAR EXPORT END     */\n";
      }
      $msg .= "  DEBUG END\n";
      if($this->log)$this->log->write($msg,NLog::std);
    //}
    return $result;

  }


/*
не храните bool!
*/
  public function get( $key ){
    
    $result = parent::get($key);
    $failTag = "";
    
    if($result !== false){


      //если у объекта есть тэги
      if($result["tags"]){
	
	$tags = parent::get(array_keys($result["tags"]));
	
	foreach($result["tags"] as $tagName => $tagValue){
	  
	  if(!isset($tags[$tagName]) || ($tagValue != $tags[$tagName])){

	    //тэг сброшен!
	    $failTag = $tagName." (".$tagValue." != ".$tags[$tagName].")";
	    $result = false;
	    break;
	  }
	}

      }
    }

    //debug
    //if($this->debugLevel >= self::standartDebugLevel){
      $msg  = "  DEBUG BEGIN\n";
      $msg .= "  FUNCTION CALLED:       get\n";
      $msg .= "  FUNCTION STATUS:       ".($result?"OK":"FAULT")."\n";
      if($result === false){
	$msg .= "  FAIL REASON:           ";

	if(empty($failTag)){
	  $msg .= "OBJECT MISS\n";
	}else{
	  $msg .= "TAG MISS\n";
	  $msg .= "  FAIL TAG:              ".$failTag."\n";
	}
      }

      $msg .= "  KEY:                   ".$key."\n";
      if($this->log->level() == NLog::debug){
	$msg .= "  VALUE:                  \n";
	$msg .= "  /*    VAR EXPORT BEGIN    */\n";
	$msg .= var_export($result["value"],true)."\n";
	$msg .= "  /*     VAR EXPORT END     */\n";
      }
      $msg .= "  DEBUG END\n";
      if($this->log)$this->log->write($msg,NLog::std);
    //}
    

    if($result){
      return $result["value"];
    }

    return false;
  }


  public function flushTag($tag){
    $time = round(microtime(true)*1000);
    $result = parent::set($tag,$time,false,$this->tagsExpiration);

    //if($this->debugLevel >= self::standartDebugLevel){
      $msg  = "  DEBUG BEGIN\n";
      $msg .= "  FUNCTION CALLED:       flushTag\n";
      $msg .= "  FUNCTION STATUS:       ".($result?"OK":"FAULT")."\n";
      
      $msg .= "  TAG:                   ".$tag."\n";
      $msg .= "  DEBUG END\n";
      if($this->log)$this->log->write($msg,NLog::std);
   //}
    
    return $result;
  }


//   public function Error($message){
// 
//     if(!$this->log_file){
//       $this->log_file = fopen($this->error_log, 'a+');
//     }
//   
//     if($this->log_file &&
//       flock($this->log_file,LOCK_EX) &&
//       fwrite($this->log_file, $message."\nTime: ".date('d M Y h:i:s')."\n---\n") &&
//       flock($this->log_file,LOCK_UN) ){
//     }else{
//       echo "Error: can't write cache log:\n";
//     }
//   }


}

 
