<?php
spl_autoload_register(function($className){
	// Fix for OSX and *nix
	$className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
	include ( dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR. $className . ".php");
});
