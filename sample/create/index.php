<?php
chdir ("..");
$_REQUEST["do"]="create";
$dt = new DateTime();
$minutemen= $dt->format('i');
if(key($_REQUEST)==$minutemen)
	include("index.php");
else{
	print '{"success":false,"error":"provide the minute to verify intention to create the table"}';
}
?>