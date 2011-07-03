<?php
/**
 * Base class for modules;
 */
abstract class module{
	private $db;
	private $req;
	private $arg;
	public  $cfg;
	public  $stmt;
	public  $new_insert_id;
	public  $query_object;
	public  $query_handle;
	public  $query_result;
	/**
	 *
	 */
		public function __construct(&$c){
			try{
				$this->cfg = $c; 
				switch ($c->driver){
				case  "mysql": $this->db=new PDO(
						"$c->driver:dbname=$c->dbase;host=$c->host;port=$c->port;",
											 $c->user,
											 $c->pass);
						break;
				case "sqlite":
						$this->db=new PDO("sqlite:".getcwd()."/".$c->dbase );
						break;
				default : throw new Exception("driver not specified");
				}

			}catch(exception $e){
				die("db-connection cannot be established.\n".$e->getMessage()
					);
			}
			$this->db-> query("SET NAMES 'utf8';");

			$this->req=&$c->request;
			
			try{
				if(!$q=$this->db->query("SELECT max(id) FROM {$this->req['table']};"))
					throw new exception();
				else
					$this->new_insert_id=$q->fetch(PDO::FETCH_NUM,0);
			}catch(exception $e){
				$do = $this->req["do"];
				$this->req["do"]="create";
				$this -> query_construct();
				$this -> query_execute();
				if($this->db->errorCode()>0)
					die("db-connection cannot reach datatable.\n".$e->getMessage());
				$this->req["do"]=$do;
			}
		}
	/**
	 * The key request options:
	 *  ../module/?number		 ; meaning depends on do-request or implementation
	 *  ../module/?function-call ; the value must be null ; functionname has to be first key
	 *	../module/?do=Action     ; create, insert, select, update, delete, set, get
	 *	../module/select/?columns (&from) &where &having &group &order &limit &offset
	 *
	 *	array key
	 *	columns  ( , from , where , having , group , order )
	 *  columns may be expressed as an array. 
	 */
		public function request_prepare(){
	
		/**
		 * DO
		 * if no 'do' action is specified ,bringing up the 'describe' info-data.	
		 */			
		if(!$this->req["do"]) $this->req["do"]="describe";
		
		/**
		 * FUNCTIONCALL
		 * if first argument is empty, then assumed to be a special case
		 */
		if(current($this->req)==="" && count($this->req)){

			//skip if the first key is a number 
			if(preg_match("/^[0-9]$/",key($this->req))){

				$w= explode("&",$_SERVER["QUERY_STRING"]);//NOTE: a not so nice global
				$w= $w[0];
				$w= explode(" ",urldecode($w));
				
				//SHORTHAND FUNCTION CALL
				if(count($w)==1 && $this->req["do"]=="describe"){
					$this->req["do"] 	  = $w[0];
				}	
			}
		}

		if($this->req["columns"]){
			if(is_array($this->req["columns"]))
				$this->req["columns"]= implode(",",($this->req["columns"]));
			$this->request_secure("columns");
		}
		if($this->req["from"]){
			if(is_array($this->req["from"]))
				$this->req["from"]= implode(",",($this->req["from"]));
			$this->request_secure("from");
		}
		if($this->req["where"]){
			$this->request_secure("where");
		}
		if($this->req["having"]){
			$this->request_secure("having");
		}
		if($this->req["group"]){
			$this->request_secure("group");			
		}
		if($this->req["order"]){
			$this->request_secure("order");		
		}
		if($this->req["limit"]){
			$this->request_secure("limit");			
		}
		if($this->req["offset"]){
			$this->request_secure("offset");			
		}
	}
	/**
	 * 
	 */
		public function request_secure($key){
			$tmp = explode(" ",str_replace(array("\n","\t","\r")," ",$this->req[$key]));
			$swords=array("grant","set","alter");
			foreach($tmp as $word){
				if(in_array(strtolower($word),$swords)){
					trigger_error(
						"$key expr has words[".implode(",",$swords)."];these are not allowed",
						E_USER_ERROR);
					$this->req[$key]=" ";
				}
			}			
		}
	/**
	 *
	 */
		public function check_signals(){
		//Observer And Or Flags
		}
	/**
	 *
	 */
		public function query_construct(){
			
			//constraint: a cfg-query must exist, so ...
			$this->stmt = $this->cfg->query->{$this->req["do"]};
					
			//special construction with internal function
			if(method_exists(get_class($this),$this->req["do"])){
				$this->{$this->req["do"]}();
			}else{
				$this->query_object= new sql_constructor(&$this->stmt,&$this->req,&$this->cfg);
			}	
		}
	/**
	 *
	 */
		public function query_execute(){

				$this->query_handle = new sql_executer(&$this->db);

				$this->query_result= new stdClass();	
				$this->query_result->success = false; 
				$this->query_result->error = ""; //sql_fetcher

				if($this->query_object->stmt)
				
					foreach($this->query_object->stmt AS $idx => &$_){
					
					$method=$_->method;
					$result=$_->result;
					
					if(!isset($result))
													$this->query_handle->$method($_->string);
					else 				
						$this->query_result->$result=$this->query_handle->$method($_->string);

					if($this->db->errorCode()!=0)
						$this->query_result->error.=implode("\t",$this->db->errorInfo())."\n";
					}
				else{
					$this->query_result->error="api call failed : ".$this->req["do"]; //TODO:hunt down in more detail
				}

				if(!$this->query_result->error){
					$this->query_result->success=true;
					unset($this->query_result->error);
				}		
		}
	/**
	 *
	 */
		public function query_result(){
		//cursor ...
		//post processing before response
		}
	/**
	 *
	 */
		public function send_response(){
		
			if(DEBUG){
				$this->query_result->statement=&$this->stmt;	
				$this->query_result->requested=&$this->req;
			}

			//TODO:if respond_in json: so far no alternative so what.
			print json_encode($this->query_result);		
		}	
	/**
	 *
	 */
		public function create(){

			$this->query_object= new sql_constructor(&$this->stmt,&$this->req,&$this->cfg);

			$cols = array();

			foreach($this->cfg->columns as $key => $def){
				array_push($cols,"$key $def");
			}

			foreach($this->stmt as &$qobj){
				$qobj->string=str_replace(":table",get_class($this),$qobj->string);
			}

			$this->stmt[1]->string = str_replace(":columns",implode(",",$cols),$this->stmt[1]->string);


			$this->query_object->stmt=&$this->stmt;		
		}
		
}

/**
 * 
 * The executor simplifies fetching different kind of records
 *
 */
class sql_executer{

	private $db;
	public function __construct(&$db){
		$this->db	= &$db;
	}
	public function fetch_field($stmt){
		if($q = $this->db->query($stmt))
		return @array_pop($q->fetch(PDO::FETCH_NUM,0));	
	}
	public function fetch_number($stmt){
		if($q = $this->db->query($stmt))
		return @0+array_pop($q->fetch(PDO::FETCH_NUM,0));	
	}
	public function fetch_all_assoc($stmt){
		if($q = $this->db->query($stmt))
		return @$q->fetchAll(PDO::FETCH_ASSOC);	
	}
	public function execute($stmt){
		return $this->db->query($stmt);		
	}	
}

/**
 * 
 * The sql_constructor takes a SQL-Object from configuration
 * and merges it with the Request
 * the configuration is stored in config.inc
 * 
 * query -> statements -> sql-object 
 * 
 * the json query-object is an array of all available queries for this module
 * 
 * the statements can be an array of sql-object that describe one transaction
 * 
 * the json sql-object:
 * {
 *	"prepare":[{"name":"table","required":true}]
 *	,
 *	"string":"SELECT * FROM sqlite_master UNION SELECT 'sqlite_version' as type,sqlite_version() as name,null as tbl_name,null as rootpage,null as sql  ORDER BY type "
 *	,
 *	"method":"fetch_all_assoc"
 *	,
 *	"result":"rows"
 *	}
 * 
 * 	the prepare-object is an array of Request-keys
 *  properties for the object-variant are:
 *  name		 string  obligate;
 *  defaultValue mixed	 value defaults to;
 *  required	 bool    throws an error , if the value is not set;
 *  func		 string  param_sum , param_binsum , param_concat
 *  args		 mixed   arguments for func
 *  escape		 bool	 escape value as sql-identifier
 *  
 *  a very special prepare key is 'query->statement[index]'
 *  like 'query->select[0]'; getting the sql from the config
 *  this way sql can be embedded in the current query
 *  
 *  @todo delegate many ifs to a functionCall 
 *  >>>   if $key == $value  to  $this->handle$value();
 *  
 */
class sql_constructor{
	public $reserved_words=array("table","columns","values","set","from","where","on","join","union","group","having","order"); //limit offset 
	public $stmt;
	public $cfg;
	/**
	 * 
	 * Constructor
	 * @param object $stmt
	 * @param mixed() $req
	 * @param object $cfg
	 */
	public function __construct(&$stmt,&$_,&$cfg){

		$this->cfg=&$cfg;
				
		if(@count($stmt))	foreach ($stmt as &$qobj){
		
		if($qobj->prepare)	foreach($qobj->prepare as $i => $key){
			/*
			 * prepare key is an object
			 */
			if(is_object($key)){
				$obj=$key; $key= $obj->name;

				//DEFAULT-VALUE
				if(isset($obj->defaultValue)){
					$_[$key]=$obj->defaultValue;
					}
				//REQUIRED-VALUE		
				if($obj->required && !isset($_[$key])){
					trigger_error("The required Parameter \"$key\" has no Value",E_USER_ERROR);
					}
				//REDUCE-VALUE
				if ($obj->func){
					$func = $obj->func;
					$_[$key]=$this->$func($_[$key],$obj->args);
					}
				
				}
			/*
			 * an embedded sql from config eg: cfg->select[0]
			 */
			if(preg_match("/^query->.*/",$key)){
				list($statement,$index) =explode("[",str_replace("query->","",$key));
				$statement=trim($statement);
				$index=trim($index);
				$index = substr($index, 0,strpos($index, "]"));
				$_[$key]=$this->cfg->query->$statement;
				$_[$key]=$_[$key][$index]->string;
				//drop command delimiter
				for($i=strlen($_[$key])-1;$i>0;$i--){
					if (preg_match("/[\s\n\t\r;]/",$_[$key][$i])){
						$_[$key][$i]="";
					}
					else $i=0;
				}
				$_[$key]=str_replace("\0","",$_[$key]);
			}
			if($key=="columns")if(!$_["columns"])$_["columns"]=" * ";
				
			/**
			 * SQL-COMMAND WORDS
			 * the Request are built just like in SQL, with some additional rules.
			 * FROM
			 * defaults to  'from folder|class|table-name'
			 * WHERE
			 * Between Operands and Operator must be a Spacechar, 
			 * eg: 'x > y' is valid, 'x>y' is not.
			 * Equality can be expressed as 'eq' 
			 * despite the possibilities to mask '=';
			 */
			if($key=="from"){
				if(!preg_match("/^from /",$_["from"])){
					if($_["from"])$_["from"] ="from ".$_["from"];
					else $_["from"]="from ".$_["table"];
					}
				}
			if($key=="where") if($_["where"]) if(!preg_match("/^where /",$_["where"]))  $_["where"] ="where ".preg_replace("/ eq /"," = ", $_["where"]);	 
			if($key=="having")if($_["having"])if(!preg_match("/^having /",$_["having"]))$_["having"]="having ".$_["having"];
			if($key=="group") if($_["group"]) if(!preg_match("/^group /",$_["group"]))  $_["group"] ="group by ".$_["group"];
			if($key=="order") if($_["order"]) if(!preg_match("/^order /",$_["order"]))  $_["order"] ="order by ".$_["order"];
			if($key=="limit") if($_["limit"]) if(!preg_match("/^limit /",$_["limit"]))  $_["limit"] ="limit ".$_["limit"];
			if($key=="offset")if(isset($_["offset"]))if(!preg_match("/^offset /",$_["offset"]))$_["offset"]="offset ".$_["offset"];
		
			if($key=="set"){
				$cols=array();
				foreach($this->cfg->columns as $k => $def){
					if(isset($_[$k])){
						$_[$k] = strlen($_[$k]) 
						? ((preg_match("/^[0-9.-eE]{1,64}$|[ \t\s]/",$_[$k]))?$_[$k]:"'$_[$k]'")
						:"null";
						array_push($cols,'"'.$k.'"='.$_[$k]);
					}	
				}
				$_["set"]="set ".implode(",",$cols);			
			}
			/*
			 * take columns as is, if columns has value
			 * else build from submitted key-value-pairs
			 */
			if($key=="columns" && !$_[$key]){
				$cols=array();

				foreach($this->cfg->columns as $_key => $def){
						if($_[$_key])
							array_push($cols,'"'.$_key.'"');
				}
				$_[$key]=implode(",",$cols);
				
			}				
			if($key=="values"){
				$cols=array();
				foreach($this->cfg->columns as $_key => $def){
					//
					//workaround until sqlite acts correctly to null in column having a default value.
					if($_key=="id" && $this->cfg->driver=="sqlite")
							array_push($cols,"ifnull((SELECT max(id) FROM $_[table])+1,1)");
					elseif($_[$_key]){
							array_push($cols,"'$_[$_key]'");
					}
				}
				$_["values"]=implode(",",$cols);			
			}

			//The value
			$k=$_[$key];
			//
			
			//OBJECT
			if(is_object($obj)){
				if($obj->escape)
					$qobj->string=str_replace(":".$key,'"'.$k.'"',$qobj->string);//TODO:PDO::mask_identifier
				else
					$qobj->string=str_replace(":".$key,$k,$qobj->string);
			}
			//STANDARD-VALUE
			elseif(!in_array($key,$this->reserved_words)){		
			  	$qobj->string=str_replace(":".$key,(strtolower($w[2])=="null" || !strlen($k) ) 
								 ? "null" 
								 : ((preg_match("/^[0-9.-eE]{1,64}$/",$k))?$k:"'$k'"),$qobj->string);			
				}
				
			//RESERVED-WORDS (composed SQL-Fragments >> all or nothing)
			else{
				switch($key){
					//case "from" : $k= "FROM $k";break;
				}
				$qobj->string=str_replace(":".$key,$k,$qobj->string);
				}
			}
			
		}
		$this->stmt= &$stmt;		
	}
	/**
	 * 
	 * typical arrayAggregation sum
	 * @param number() $a
	 */
	public	function param_sum(&$a){
			$a = array_sum($a);
		}
	/**
	 * 
	 * typical arrayAggregation binary summary
	 * @param bool() $a
	 */
	public	function param_binsum(&$a){
			$sum=0;
			foreach($a as $i => $value){
				if($value) $sum+=1<<$i;
			}
			$a=$sum;
		}
	/**
	 * 
	 * typical arrayAggregation concatenation
	 * @param mixed() $a
	 * @param char $delim
	 * @param char $mask
	 */
	public	function param_concat(&$a,$delim=",",$mask=""){
			$concat=array();
			foreach($a as $i => $value) $concat[$i]=$value;
			$a=implode($delim,$concat);
		}		
}
?>