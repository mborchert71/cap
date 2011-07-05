<?php	
/**
 *
 * folder-name is module-name is class-name
 */
$c=json_decode(file_get_contents("config.inc"));
$c->request = &$_REQUEST;

if(!$c->table || !$c->database || !$c->path){
	$adir  = explode("/",str_replace(array("\\"),"/",getcwd()));	
	$class = $c->table 	 = array_pop($adir);
					 $c->database= array_pop($adir);
					 $c->path    = implode("/",$adir);
	}
					 
if(!defined(DEBUG)){
	define (DEBUG, @$_REQUEST["debug"]);
	}

if(!defined(MODULE_SRC)){
	define (MODULE_SRC,"../module.php");
	include(MODULE_SRC);
	}

// include($class.".php");
eval ( "class $class extends module{}" );

$MOD = new $class(&$c);
						//$datahandle=&$MOD->get_data_handle();
$MOD -> request_prepare();
						//$request	= &$MOD->get_request_data();
$MOD -> check_signals();
						//$param		=	&$MOD->get_arguments();
$MOD -> query_construct();
						//$object		= &$MOD->query_object;
$MOD -> query_execute();
					 //$handle 		= &$MOD->query_handle;
$MOD -> query_result();
					 //$result 		= &$MOD->query_result;
$MOD -> send_response();

?>