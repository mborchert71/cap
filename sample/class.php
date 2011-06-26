<?php
/**
 * Class handle the data-table called "status" of the time-line-project;
 */
if(!defined(MODULE_SRC)){
	define (MODULE_SRC,"module.php");
	include(MODULE_SRC);
}

eval ( "class {$_REQUEST['table']} extends module{}" );

?>