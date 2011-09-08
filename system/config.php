<?php


class NConfig implements ArrayAccess, Iterator, Countable {
    static private $configs   = array();
    private $file = null;
    private $values = null;
  
    static private $base = "";

    static function setBase($base){
      self::$base = $base;
    }

/*Получить конфиг
$name может быть строкой вида filename[/section[/array[/...]]]
где filename - имя файла с конфигами
section - секция в этом файле
array  - массив в этой секции, этого файла с конфигами
При этом вы можете вызвать например config::getConfig("config.ini/section"), если где-либо до этого был выполнен config::getConfig("config.ini")
и необходимо вызвать config::getConfig("config.ini")->section->array или config::getConfig("config.ini/section")->array 
до того как вы сможете вызывать config::getConfig("config.ini/section/array")
*/
    static function getConfig($name){
      $name = str_replace("base",self::$base,$name);
      $result = NULL;
      if(isset(self::$configs[$name])){
	$result = self::$configs[$name];
      }else{
	$result = new NConfig($name);
	if($result->values = @parse_ini_file($name,true)){

	  self::$configs[$name] = $result;
	  foreach($result->values as $key=>$value){
	    $result->$key;
	  }
	}else{
	  unset($result);
	  $result = null;
	}
	  
      }
      return $result;
    }
  
/*
Удалить из кэша информацию о конфиге
При этом если вы например вызвали config::deleteConfig("config.ini"), то config::getConfig("config.ini/section") останется доступным
*/
    static function deleteConfig($name){
      $name = str_replace("base/",self::$base,$name);
      if(isset(self::$configs[$name]))
	unset(self::$configs[$name]);
      return true;
    }


    private function __construct($file) {
        $this->file = $file;
    }

/*
Вы можете получать компоненты конфига через __get по его имени
*/
    function __get($name) {
      $name = str_replace("base/",self::$base,$name);
      $result = NULL;
     
      if(isset($this->values[$name])){
	//object result
	if(is_array($this->values[$name])){
	  
	  if(isset(self::$configs[$this->file."/".$name])){
	    $result = self::$configs[$this->file."/".$name];
	  }
	  else{
	    $result = new NConfig($this->file."/".$name);
	    
	    $result->values =& $this->values[$name];
	    self::$configs[$this->file."/".$name] = $result;
	  }
	}
    
	//not object result
	else{
	  $result = $this->values[$name];
	}

      }
      return $result;
    }

  public function values(){
    return $this->values;
  }


    public function offsetSet($offset,$value) {
      //модель только для чтения!
     }

     public function offsetExists($offset) {
      return isset($this->values[$offset]);
     }

     public function offsetUnset($offset) {
         //модель только для чтения!
     }

     public function offsetGet($offset) {
         return $this->$offset;
     }

     public function rewind() {
         reset($this->values);
     }

     public function current() {
         return current($this->values);
     }

     public function key() {
         return key($this->values);
     }

     public function next() {
         return next($this->values);
     }

     public function valid() {
         return $this->current() !== false;
     }    

     public function count() {
      return count($this->values);
     }

}