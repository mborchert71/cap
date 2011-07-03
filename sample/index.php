<?php	
/**
 *
 */
define ("DEBUG",@$_REQUEST["debug"]); 

if(!defined(MODULE_SRC)){
	define (MODULE_SRC,"module.php");
	include(MODULE_SRC);
}

$c=json_decode(file_get_contents("config.inc"));
$c->request=&$_REQUEST;

/*
 * folder-name is module-name is class-name
 */
$_REQUEST["table"]=array_pop(explode("/",str_replace(array("\\"),"/",getcwd())));
// include ($_REQUEST["table"].".php");
eval ( "class {$_REQUEST["table"]} extends module{}" );

$MOD = new $_REQUEST["table"](&$c);
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