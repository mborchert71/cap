<?php	
/**
  * This is the file to mess around with ... when tuning and tweaking ;)
  */
define ("DEBUG",@$_REQUEST["DEBUG"]); 

//TODO:AUTH,$tmp_user_name="root";

/*
 * folder-name is module-name is class-name
 */
$_REQUEST["table"]=array_pop(explode("/",str_replace(array("\\"),"/",getcwd())));

include("class.php");

$c=json_decode(file_get_contents("config.inc"));

$MOD = new $_REQUEST["table"](&$c);
						//$datahandle=&$MOD->get_data_handle();
$MOD -> prepare_request();
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