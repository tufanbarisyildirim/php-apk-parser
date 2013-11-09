<?php    
    spl_autoload_register(function($className){
        include ( '..\\lib\\' . $className . ".php");
    });
