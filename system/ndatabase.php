<?php
/*
Data base connection class

*/
class NDataBase{

  /*уровень журналирования
*/
  //const noneDebugLevel 	   = 0; //ничего
  //const standartDebugLevel = 1;//в лог только ошибки
  //const proDebugLevel      = 2;//в лог все запросы

/*Стандартное имя соединения с бд
*/
  const defaultName  = "default";


/*Параметры соединения
*/
  private $hostName	= ""; 		// read write
  private $userName	= ""; 		// read write
  private $password	= ""; 		// read write
  private $dataBaseName	= "";		// read write
  private $charset	= "utf8";		// read write
  //private $error_log	= "";		// read write
  //private $debugLevel = self::standartDebugLevel;	// read write
  public $log		= NULL;

  private $name = self::defaultName;	// name() read

  //private $log_file = false;
  private $isOpen = false;	// isOpen() read
  private $last_result = false;
  private $resource = false;

  private $query_count = 0;
 
  
  private static $dataBases = array();


/*
  public static function addDataBase($dbName = "default")
  Добавляет базу данных в список соединений баз данных.
  Если соединение с базой данных с именем $dbName уже существует, то оно будет удалено.
  Возвращает базу данных.

  Если аргумент $dbName не задан, то вновь добавленное соединение становится для приложения
  соединением по умолчанию, и последующие вызовы dataBase() без аргумента с именем соединения
  будут возвращать соединение по умолчанию. Если $dbName предоставляется, используйте
  dataИase($dbName) чтобы найти соединение.
  
  Внимание: Если вы добавили соединение с именем, совпадающим с именем существующего соединения,
  то старое соединение будет замещено новым. Если вы вызовите эту функцию более одного раза без
  заданного $dbName, то соединение по умолчанию будет замещено этим соединением.

  Перед использованием соединение должно быть инициализировано. например, вызовом всех или
  одной из функций setDatabaseName(), setUserName(), setPassword(), setHostName(), setPort() и
  и, в заключение, open().
*/


  public static function addDataBase($dbName = self::defaultName){
    if(isset(self::$dataBases[$dbName])){
      unset(self::$dataBases[$dbName]);
    }

    self::$dataBases[$dbName] = new NDataBase();
    self::$dataBases[$dbName]->name = $dbName; 
    
    return self::$dataBases[$dbName];
  }


/*
  public static function dataBase($dbName = "default")
  Возвращает соединение с базой данных с именем $dbName. Оно должно быть заранее добавлено с 
  помощью addDatabase(). Если $dbName не задано, используется соединение по умолчанию. Если $dbName 
  отсутствует в списке баз данных, возвращается 0.
*/

  public static function dataBase($dbName = self::defaultName){
    if(isset(self::$dataBases[$dbName])){
      return self::$dataBases[$dbName];
    }else{
      return 0;
    }
  }

/*
  public static function removeDataBase($dbName = "default")
  Удаляет соединение с именем $dbName. Если $dbName не задано, удаляется соединение по умолчанию. Если $dbName 
  отсутствует в списке баз данных, то нчиего не происходит.
*/

  public static function removeDataBase($dbName = self::defaultName){
    if(isset(self::$dataBases[$dbName])){
      unset(self::$dataBases[$dbName]);
    }
    return true;
  }


/*
Конструктор*/
  function __construct(){
    $this->log = NLog::addByConfig( NConfig::getConfig("base/log"), "db" );
  }

/*
Деструктор
Если соединение было открыто, то оно закрывается, если файл логов был открыт, то он так же закрывается.
*/
  function __destruct() {
    if($this->isOpen()){
      $this->close();
    }

    if($this->log){
      unset($this->log);
    }

    //if($this->log_file){
   //   fclose($this->log_file);
    //}

  }

  /* DB connect name getter */
  public function name(){
    return $this->name;
  }

  /*
  Host Name geter and seter
  */
  public function setHostName($hostName){
    $this->hostName = $hostName;
  }
  public function hostName(){
    return $this->hostName;
  }

  /*
  DB name geter and seter
  */
  public function setDataBaseName($dbName){
    $this->dataBaseName = $dbName;
  }
  public function dataBaseName(){
    return $this->dataBaseName;
  }

  /* user name geter and seter */
  public function setUserName($userName){
    $this->userName = $userName;
  }
  public function userName(){
    return $this->userName;
  }

  /* password geter and seter */
  public function setPassword($password){
    $this->password = $password;
  }
  public function password(){
    return $this->password;
  }

  /* debug lbl geter and seter */
  //public function setDebugLevel($lvl){
  //  $this->debugLevel = $lvl;
  //}
  //public function debugLevel(){
  //  return $this->debugLevel;
  //}


/*
Если соединение установленно и готово к использованию возвращается true
*/
  public function isOpen(){
    return $this->isOpen;
  }

  /* charset geter and seter */
  public function setCharset($set){
    $this->charset = $set;
  }
  public function charset(){
    return $this->charset;
  }

  /* log file geter and seter */
  //public function setLogFileName($filename){
  //  $this->error_log = $filename;
  //}
  //public function logFileName(){
  //  return $this->error_log;
  //}

  public function resource(){
    return $this->resource;
  }


/*
Можно установить параметры соединения особым конфигом, который должен содержать поля
host,name,user,password,debug,log,charset
*/
  
   public function loadConfig(NConfig $config){
    $this->setHostName		($config->host);
    $this->setDataBaseName	($config->name);
    $this->setUserName		($config->user);
    $this->setPassword		($config->password);
    //$this->setDebugLevel	($config->debug);
    //$this->setLogFileName	($config->log);
    $this->setCharset		($config->charset);

   }


/*Открывает соединение с бд
Возвращает true в случае успеха, иначе false
*/
  public function open(){
    $errors = array();

//1{
    //если открвыто,то ничего не делаем, только капаем в логи если нужно
    if(!$this->isOpen()){
      
      //соединение
      if (! $this->resource = @mysql_connect($this->hostName,$this->userName,$this->password) ){
	$this->resource = false;
	$errors[]=mysql_error();
      }
      //выбор бд
      elseif (!@mysql_select_db($this->dataBaseName, $this->resource)){
	$this->resource = false;
	$errors[]=mysql_error($this->resource);
      }
      //кодировка
      elseif (!mysql_set_charset($this->charset,$this->resource)){
	$this->resource = false;
	$errors[]=mysql_error($this->resource);
      }
      else{
	$this->isOpen = true;

	
	//if ($this->debugLevel == self::proDebugLevel){
	  $msg  = "  DEBUG BEGIN\n";
	  $msg .= "  FUNCTION CALLED:     open\n";
	  $msg .= "  FUNCTION STATUS:     OK\n";
	  $msg .= "  DB SELECTED:         ".$this->name."\n";
	  $msg .= "  DEBUG END\n";
	  if($this->log)$this->log->write($msg,NLog::debug);
	//}
      }

    }
//1}
	
    //капаем в логи если ошибки
    if  ((sizeof($errors) > 0) /*&& ($this->debugLevel > self::noneDebugLevel)*/){
      $msg  = "  DEBUG BEGIN\n";
      $msg .= "  FUNCTION CALLED:     open\n";
      $msg .= "  FUNCTION STATUS:     FAULT\n";
      
      foreach($errors as $error){
	$msg .= "  STATUS REASON:       ".$error."\n";
      }
      $msg .= "  DEBUG END\n";
      if($this->log)$this->log->write($msg,NLog::std);
    }

    return ( sizeof($errors)==0 );
  }


/*
Закрывает соединение
Всегда возвращает true
*/
  public function close(){
    //print_r($this->name);
    //print_r($this->resource);
    if ($this->isOpen() && $this->resource) {
      $this->isOpen = false;
      mysql_close($this->resource);
      $this->resource = false;
      //if ($this->debugLevel == self::proDebugLevel){
	$msg  = "  DEBUG BEGIN\n";
	$msg .= "  FUNCTION CALLED:     dbDisconnect\n";
	$msg .= "  FUNCTION STATUS:     OK\n";
	$msg .= "  DEBUG END\n";
	if($this->log)$this->log->write($msg,NLog::debug);
      //}
    }
    return true;
  }

/*
Выполняет запрос $query в установленном соединение
Только для запросов SELECT, SHOW, EXPLAIN, DESCRIBE, возвращает указатель на результат запроса,
 или FALSE если запрос не был выполнен. 
В остальных случаях, возвращает TRUE в случае успешного запроса и FALSE в случае ошибки. 
Значение не равное FALSE говорит о том, что запрос был выполнен успешно.
*/
  public function exec($query){
    
    if($this->isOpen()){

      $resource = mysql_query($query, $this->resource);
      if($resource){
	//if ($this->debugLevel == NDataBase::proDebugLevel){
	  $msg  = "  DEBUG BEGIN\n";
	  $msg .= "  FUNCTION CALLED:     exec\n";
	  $msg .= "  FUNCTION QUERY:      \n".$query."\n";
	  $msg .= "  FUNCTION STATUS:     OK\n";
	  $msg .= "  DEBUG END\n";
	  if($this->log)$this->log->write($msg,NLog::debug);
	  
	//}
      }else{
	$msg  = "  DEBUG BEGIN\n";
	$msg .= "  FUNCTION CALLED:     exec\n";
	$msg .= "  FUNCTION QUERY:      \n".$query."\n";
	$msg .= "  FUNCTION STATUS:     FAULT\n";
	$msg .= "  STATUS REASON:       ".@mysql_error($this->resource)."\n";
	$msg .= "  DEBUG END\n";
	if($this->log)$this->log->write($msg,NLog::std);
      }

      //if($this->debugLevel == NDataBase::proDebugLevel){
	$this->query_count ++;
      //}
      return $resource;
    }

    return false;
  }

/*
Транзакции
*/

  public function transaction (){
    return $this->exec("START TRANSACTION");
  }
  public function commit (){
    return $this->exec("COMMIT");
  }
  public function rollback (){
    return $this->exec("ROLLBACK");
  }



/*
капанье в логи
Если файл не был открыт, то функция попытается его открыть и будет держать открытым пока вы не вызовите метод close()
*/
//   public function Error($message){
// 
//     if(!if($this->log->)$this->log_file){
//       if($this->log->)$this->log_file = fopen($this->error_log, 'a+');
//     }
//   
//     if(if($this->log->)$this->log_file &&
//       flock(if($this->log->)$this->log_file,LOCK_EX) &&
//       fwrite(if($this->log->)$this->log_file, $message."\nTime: ".date('d M Y h:i:s')."\n---\n") &&
//       flock(if($this->log->)$this->log_file,LOCK_UN) ){
//     }else{
//       echo "Error: can't write db log:\n";
//     }
//   }

/*
Возвращает колическтво вызовов exec для этого соединения
*/
  public function queryCount(){
    return $this->query_count;
  }
}




/*
Data Base row class
*/

class NDataBaseRow{

  private $row = array(); // set or not set
  private $types = array();// not set, 1, 2
/*
0 - value
1 - если False или не задано, этого поля нет в бд, иначе если 1, то это обычное поле, если 2, то это поле содержащее mysql код
Обычные поля прогоняются через mysql_real_escape_string когда вы используете __toString() или toInsert()

*/

/*
Конструктор
$row - ссылка на ассоциативный массив с полями, где ключ - имя поля, а значение - значение полями
Все поля добавленные через конструктор считаются не существующими в бд
Массив для работы в дальнейшем не используется, так как нужные его части копируются.
*/
  function __construct(array &$row = array()){
    //foreach($row as $field => $value){
    //  $this->row[$field][0] = $value;
    //}
    $this->row = &$row;
  }
  
  public function setArray(array &$row = array()){
    $this->row = &$row;
  }

  function __destruct() {
    //unset($this->row);
  }
  
  /* field geter and seter */
  public function field($name){
    if(isset($this->row[$name]))
      return $this->row[$name];
    return NULL;
  }
  
/* 
$name - имя
$value - значения
$mysql - если заданно,то значение не будет обрабатываться через mysql_real_escape_string. Для использования поле должно существовать в бд

Возвращает true если вы установили существующее в бд поле
*/
  public function setValue($name,$value,$mysql = false){
    $this->row[$name] = $value;
    
    if(isset($this->types[$name])){
      if($mysql)
	$this->types[$name] = 2;
      return true;
    }

    return false;
  }

/*Помечает поля как существующее в бд
*/
  public function addField($name,$mysql = false){      
    if($mysql){
      $this->types[$name] = 2;
    }
    else{
      $this->types[$name] = 1;
    }
  }

  function __call($field, $args) {
    $field = strtolower ($field);
    //сетер
    if(substr($field,0,3) == "set"){
      if(isset($args[0])){
	if(isset($args[1])){
	  return $this->setValue( substr($field,3) ,$args[0],$args[1]);
	}else{
	  return $this->setValue( substr($field,3) ,$args[0]);
	}
      }
    }
    //гетер
    else{
      return $this->field($field);
    }
  }
  
  function __get($name){
    return $this->$name();
  }
  function __set($name,$value){
    $name = "set".$name;
    return $this->$name($value);

  }


/* to SQL string 
Получет строку вида `fieldName` = 'value' [,....]


*/

  public function __toString(){
    $result = "";
    foreach($this->row as $field => $value){
      
      if( isset( $this->types[$field] ) ){

	if(!empty($result)){
	  $result .= " ,";
	}	

	$result .= "`".mysql_real_escape_string($field)."` = ";


	//если есть значение то в зависимости от пометки делаем что-нибудь
	if($value === NULL){
	  $result .= "NULL";
	}elseif( $this->types[$field] == 1 ){
	  $result .= "'".mysql_real_escape_string( $value )."'";
	}elseif( $this->types[$field] == 2 ){
	  $result .= $value;
	}else{
	  $result .= "NULL";
	}

      }

    }
    return $result;
  }
  
/*
Преобразует в строку вида
[ (`fielName` [,...] ) VALUE] (value [,...])
если $v == false, то имяна самих полей и ключевое слово VALUE не используется.
*/
  public function toInsert($v = true){
    $fields = "";
    $values = "";
    foreach($this->row as $field => $value){
      
      if( isset( $this->types[$field] ) ){

	if(!empty($fields)){
	  $fields .= " ,";
	  $values .= " ,";
	}	

	$fields .= "`".mysql_real_escape_string($field)."`";


	//если есть значение то в зависимости от пометки делаем что-нибудь
	if($value === NULL){
	  $values .= "NULL";
	}elseif( $this->types[$field] == 1 ){
	  $values .= "'".mysql_real_escape_string( $value )."'";
	}elseif( $this->types[$field] == 2 ){
	  $values .= $value;
	}else{
	  $values .= "NULL";
	}

      }

    }
    if($v)
      return "(".$fields.") VALUE (".$values.")";
    else
      return "(".$values.")";
  }

}

/*
 Data Base query class

*/

class NDataBaseQuery{
  
  private $dataBase = NULL;

  private $resource = NULL;

  /*
в качестве ресурса может быть использованн массив, а не результат работы запроса
Это дает возможность получить данные из бд каким-то иным способом, а затем использовать полученные результаты для заполнения экземпляров классов на основе NDataBaseQuery
При вызове любого метода для взаимодействия с бд, таких как например exec этот указатель будет сброшен и в дальнейшем будет использваоться $resource
*/
  private $array_resource = NULL; // 
    
  protected $rowInterfaceObject = NULL;


/*
Конструктор
$dataBase - соединение с бд, если не заданно используется соединение по умолчанию.
*/
  function __construct(NDataBase $dataBase = NULL){
    if($dataBase){
      $this->dataBase = $dataBase;
    }else{
      $this->dataBase = NDataBase::dataBase();
    }
  }

/*деструктор
*/
  function __destruct() {
    $this->free();
  }

  /* db seter */
  public function setDataBase(NDataBase $dataBase){
    if($dataBase){
      $this->dataBase = $dataBase;
      return true;
    }
    return false;
  }
  

/* execute query */
  public function exec($query){
    if(!$this->dataBase){
      //secho "db fail";
      return false;
    }

    $this->resource =  $this->dataBase->exec($query);
    $array_resource = NULL;
    return $this->resource;
  }

/*Устанавливает массив
$array в качестве ресурса запроса
*/
  public function setArrayResource(array &$array){
    if($this->resource){
      @mysql_free_result($this->resource);
      $this->resource = NULL;
    }
    
    $this->array_resource = &$array;
    //reset($this->array_resource);
  }


/*
Транзакции
*/

  public function transaction (){
    if(!$this->dataBase){
      //secho "db fail";
      return false;
    }
    $this->resource =  $this->dataBase->transaction();
    $array_resource = NULL;
    return $this->resource;
  }
  public function commit (){
    if(!$this->dataBase){
      //secho "db fail";
      return false;
    }
    $this->resource =  $this->dataBase->commit();
    $array_resource = NULL;
    return $this->resource;
  }
  public function rollback (){
    if(!$this->dataBase){
      //secho "db fail";
      return false;
    }
    $this->resource =  $this->dataBase->rollback();
    $array_resource = NULL;
    return $this->resource;
  }

/*
Получает следующий ассоциативный массив из ресурса запроса
*/
  /*protected*/public function nextAssoc(){
    if($this->resource){
      return mysql_fetch_array ($this->resource, MYSQL_ASSOC);
    }else if($this->array_resource){
      $result =  current($this->array_resource);
      next($this->array_resource);
      return $result;
    }
    return false;
  }

/*
Получает следующий NDataBaseRow
*/
  public function next(){
    $result = NULL;
    if ($row = $this->nextAssoc()){
      $result =  new NDataBaseRow($row);
      unset($row);
    }
    return $result;
  }	

  public function setInterfaceObject(NDataBaseRow $object){
    $this->rowInterfaceObject = $object;
  }


  public function nextInterface(){
    if(!$this->rowInterfaceObject){
      $this->setInterfaceObject(new NDataBaseRow());
    }
    if ($row = $this->nextAssoc()){
      $this->rowInterfaceObject->setArray($row);
      unset($row);
      return $this->rowInterfaceObject;
    }
    return NULL;
    
  }


/*
  Получает ассоциативный массив с порядковым номером $row_number  из ресурса запроса
  В случае если ресурсов является массив это не сдвигает его указатель на $row_number
*/
  public function seekAssoc( $row_number){
    if($this->resource){
      if(mysql_data_seek ( $this->resource, $row_number ))
	return $this->nextAssoc();
    }elseif(isset($this->array_resource[$row_number])){
      return $this->array_resource[$row_number];
    }

    return NULL;
  }

/*
  Получает NDataBaseRow с порядковым номером $row_number  из ресурса запроса
  В случае если ресурсов является массив это не сдвигает его указатель на $row_number
*/

  public function seek( $row_number){
    $result = NULL;
    if ($row = $this->seekAssoc( $row_number)){
      $result =  new NDataBaseRow($row);
      unset($row);
    }
    return $result;
  }

/*
Возвращает кол-во записей в результате
*/  

  public function count(){
    if($this->resource){
      return mysql_num_rows($this->resource);
    }else if($this->array_resource){
      return count($this->array_resource);
    }
    return false;
  }

/*Очищает русурсы
*/
  public function free(){
    if($this->resource){
      @mysql_free_result($this->resource);
      $this->resource = NULL;
    }else if($this->array_resource){
      unset($this->array_resource);
      $this->array_resource = NULL;
    }
  }

  public function insertID(){
    if(!$this->dataBase){
      return false;
    }


    if($this->dataBase->isOpen()){
      return  mysql_insert_id ( $this->dataBase->resource() );
    }else{
      return false;
    }
  }

  public function affectedRows(){
    if(!$this->dataBase){
      return false;
    }

    if($this->dataBase->isOpen()){
      return  mysql_affected_rows ( $this->dataBase->resource() );
    }else{
      return false;
    }
  }

}



/*
Таблица бд
Дает возможность производить простые select,insert,update и delete запросы
Учтите что данные полученные при использваонии методов setWhere, setLimit, setOrderBy не проверяются
Данные полученные из NDataBaseRow передаются через mysql_real_escape_string при необходимости
Для Select используется "*" для указания необходимых полей, при insert и update запросах используются только те поля, которые были помеченны методом addField.
*/

class NDataBaseTable extends NDataBaseQuery{
  
  private $table;

  private $where;
  private $orderby;
  private $limit;

  function __construct(NDataBase $dataBase = NULL){
   parent::__construct($dataBase);
  }
  function __destruct() {
    parent::__destruct();
  }

/* table name geter and seter*/
  public function setTable($name){
    $this->table = $name;
  }
  public function table(){
    return $this->table;
  }

/* where geter and seter*/

  public function setWhere($where){
    $this->where = $where;
  }
  public function where(){
    if(!empty($this->where))
      return "WHERE ".$this->where;
    return "";
  }
/* limit geter and seter*/
  public function setLimit($limit){
    $this->limit = $limit;
  }
  public function limit(){
    if(!empty($this->limit))
      return "LIMIT ".$this->limit;
  }

/*order by geter and seter*/
  public function setOrderBy($orderby){
    $this->orderby = $orderby;
  }
  public function orderBy(){
    if(!empty($this->orderby))
      return "ORDER BY ".$this->orderby;
  }

//   public function setFields(NDataBaseRow $row){
//     $this->fields = $row;
//   }
//   public function fields(){
//     return $this->fields;
//   }


/*
Делает SELECT запрос
true если exec вернул не false, иначе false
*/
  public function select(){
    if(!empty($this->table)){
      $query = "SELECT * FROM `".$this->table."` ".$this->where()." ".$this->orderBy()." ".$this->limit();
      if($this->exec($query)){
	return true;
      }
    }
    return false;
  }

/*
Вставляет в бд строку $row
*/

  public function insert(NDataBaseRow $row){
    if(!empty($this->table)){
      $query = "INSERT INTO `".$this->table."` " . $row->toInsert();

      if($this->exec($query)){
	return true;
      }
    }
    return false;
  }
  public function insertRows(array $rows){
    if(!empty($this->table)){
      $insertStr = "";
      foreach($rows as $row){
	if($row instanceof NDataBaseRow){
	  if(empty($insertStr)){
	    $insertStr .= $row->toInsert();
	  }else{
	    $insertStr .= ",".$row->toInsert(false);
	  }
	}
      }
      if(!empty($insertStr)){
	$query = "INSERT INTO `".$this->table."` " . $insertStr;
	if($this->exec($query)){
	  return true;
	}
      }
    }
    return false;

  }

/*
Заменяет строки подходящие под условие $where, на  $row
*/
  public function update(NDataBaseRow $row){
    if(!empty($this->table)){
      $query = "UPDATE `".$this->table."` SET ".$row->__toString() ." ".$this->where();
      if($this->exec($query)){
	return true;
      }
    }
    return false;
  }

}