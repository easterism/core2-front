<?php
    header('Content-Type: text/html; charset=utf-8');
    require_once("core2/inc/classes/Error.php");
	require_once("core2/inc/classes/Init.php");

try {
	
	$init = new Init();
	$init->checkAuth();

	echo $init->dispatch();
} catch (Exception $e) {
	\Core2\Error::catchException($e);
}
