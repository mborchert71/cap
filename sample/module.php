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
	public function __construct($c){
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
			$this->req=&$_REQUEST;
			
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
	 *
	 */
		public function get_data_handle(){
			return $this->db;
		}
	/**
	 *
	 */
		public function set_data_handle(&$db){
			$this->db=&$db;
		}
	/**
	 *
	 */
		public function get_request_data(){
			return $this->req;
		}
	/**
	 *
	 */
		public function set_request_data(&$req){
			$this->req=&$req;
		}
	/**
	 *
	 */
		public function get_argument($property=null){
			return $this->arg->$property;
		}
	/**
	 *
	 */
		public function set_argument($property=null,$value=null){
			$this->arg->$property=$value;
		}
	/**
	 *
	 */
		public function get_arguments(){
			return $this->arg;
		}
	/**
	 *
	 */
		public function set_arguments($arg){
			$this->arg->$arg;
		}
	/**
		* The special case first passed request:
		*
		*	../module/?functionCall
		*	../module/?do=Action
		*	../module/?Column Operator Value
		* 
		*		Single Where Condition ;White-Spaces are Mandatory;
		*		To express Equality(=) write 'is'; eg: '../module/?id is 123'.
		*		2part operators have to be connected with minus; eg:'x IS-NOT NULL'
		*		Further Arguments may be added;
		*   Please see the coresponding Query for Details.
		*
		*	../module/?functionCall&argument1=value&argument2=value&..	
		*	../module/?do=Action&argument1=value&argument2=value&..	
		*	../module/?Column Operator Value&argument1=value&argument2=value&..	
		*/
		public function prepare_request(){
		
			//No 'do' action specified default is 'api'. Bringing up the info-data.				
			if(!$this->req["do"]) $this->req["do"]="describe";
			
			//if first argument is empty, then assumed to be a special case
			if(current($this->req)==="" && count($this->req)){

				//if the first key is just a number skip the function-call and where-constructor
				if(preg_match("/^[0-9]$/",key($this->req))){
					
					try{
						$w= explode("&",$_SERVER["QUERY_STRING"]);//NOTE: a not so nice global
						$w= $w[0];
						$w= explode(" ",urldecode($w));
						
						//SHORTHAND FUNCTION CALL
						if(count($w)==1 && $this->req["do"]=="describe"){
							$this->req["do"] 	  = $w[0];
							//WHERE EXPRESSION as key=value Hash
							if($this->req["where"]){
								$tmp = explode(" ",$this->req["where"]);
								$reserved_words=array("grant","set","select","alter");
								foreach($tmp as $word){
									if(in_array(strtolower($word),$reserved_words))
										trigger_error(
													"where expr has spearwords and there for is not allowed",
													E_USER_ERROR);
								}
							$this->req["where"]="where ".$this->req["where"];  
							}
						}	
					}catch(Exception $e){
						$this->req["do"] 	  = "describe";
						$this->req["where"] = "";
					}

				}
			}
			else{
				//WHERE EXPRESSION as key=value Hash
				if($this->req["where"]){
					$this->req["where"]=preg_replace("/ eq /"," = ", $this->req["where"]);
					
					$tmp = explode(" ",$this->req["where"]);
					$reserved_words=array("grant","set","select","alter");
					foreach($tmp as $word){
						if(in_array(strtolower($word),$reserved_words))
							trigger_error(
											"where expr has spearwords and there for is not allowed",
											E_USER_ERROR);
					}
					$this->req["where"]="where ".$this->req["where"];  
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

//todo: cursor , streamer
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

//todo: where[] array concated with and  , andwhere orwhere xorwhere  = value  , queue  ... distinct and all the reserved words
class sql_constructor{
	public $reserved_words=array("table","columns","values","set","from","where","on","join","union","group","having","order"); //limit offset 
	public $stmt;
	public $cfg;
	public function __construct(&$stmt,&$_,&$cfg){
		$this->cfg=&$cfg;
		if(@count($stmt))
		foreach ($stmt as &$qobj){	
		if($qobj->prepare)	foreach($qobj->prepare as $i => $key){

			if(is_object($key)){
				$obj=$key; $key= $obj->name;

				if($key=="columns"){
					$cols=array();

					foreach($this->cfg->columns as $_key => $def){
							array_push($cols,'"'.$_key.'"');
					}
					$_REQUEST["columns"]=implode(",",$cols);
				}

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
				
			if($key=="values"){
				$cols=array();
				foreach($this->cfg->columns as $_key => $def){
					//
					//workaround until sqlite acts correctly to null in column having a default value.
					if($_key=="id" && $this->cfg->driver=="sqlite")
							array_push($cols,"ifnull((SELECT max(id) FROM $_[table])+1,1)");
					elseif($_[$_key]){
							array_push($cols,"'$_[$_key]'");
					}else
						array_push($cols,"null");
				
				}
				$_["values"]=implode(",",$cols);			
			}

			//
			//
			//
			$k=$_[$key];
			//
			//
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

		//typical arrayAggregation sum , concat , binsum
	public	function param_sum(&$a){
			$a = array_sum($a);
		}
	public	function param_binsum(&$a){
			$sum=0;
			foreach($a as $i => $value){
				if($value) $sum+=1<<$i;
			}
			$a=$sum;
		}
	public	function param_concat(&$a,$delim=",",$mask=""){
			$concat=array();
			foreach($a as $i => $value) $concat[$i]=$value;
			$a=implode($delim,$concat);
		}		
}
?>