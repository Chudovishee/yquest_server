<?php


class NLog{
  
  static private $logs = array();
  

  const all	= 0;
  const debug	= 1;
  const std	= 2;
  const none	= 3;


  public $dir		= "./logs/";
  public $ext		= ".log";
  public $maxSize	= 0; //byte
  public $level 	= 0;

  private $file = NULL;

  
  static function addByConfig(NConfig $config,$name = "system"){
    $log = self::add($name);
    
    if($log && $config && $config->dir && $config->maxSize && $config->ext & $config->level){
      $log->dir = $config->dir;
      $log->ext = $config->ext;
      $log->maxSize = $config->maxSize;
      $log->level = $config->level;

    }

    return $log;
  }
  
  static function get($name = "system"){
    if(isset(self::$logs[$name]))
      return self::$logs[$name];
  
    return NULL;
  }
  static function add($name = "system"){
    $log = NULL;
    if($log = self::get($name)){}
    else{
      $log = new NLog($name);
      self::$logs[$name] = $log;
    }

    return $log;
  }
  static function del( $name = "system" ){
    if(isset(self::$logs[$name])){
      unset( self::$logs[$name] ) ;
      self::$logs[$name] = NULL;
    }
  }


  private function __construct($name = "system"){
    if(!empty($name)){
      $this->name = $name;
    }


  }

  public function __destruct(){
    if($this->file){
      fclose($this->file);
    }
  }
  
  public function init(){
    if(!$this->file){
      if( @filesize( $this->dir.$this->name.$this->ext ) > $this->maxSize ){
	rename( $this->dir.$this->name.$this->ext , $this->dir.$this->name."_".time().$this->ext );
      }
      $this->file = fopen( $this->dir.$this->name.$this->ext, 'a+');
    }
  }

  public function write(&$message,$level = -1){
    $this->init();
    
    if(($level >= $this->level) || ($level == -1)  ){
      if($this->file &&
	flock($this->file,LOCK_EX) &&
	fwrite($this->file, $message."\nTime: ".date('d M Y h:i:s')."\n---\n") &&
	flock($this->file,LOCK_UN) ){
      }else{
	echo "Error: can't write log: ".$this->dir.$this->name.$this->ext."\n";
      }
    }
  }
  
   

  public function level(){
    return $this->level;
  }

} 
