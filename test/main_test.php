<?php
    //I used only "Equal" assertation so you can test without PHPUnit Framework.
    if(!class_exists('PHPUnit_Framework_TestCase'))
    {
        class PHPUnit_Framework_TestCase
        {
            public function assertEquals($a,$b)
            {
                assert($a == $b);
            } 
        }
    }



    include '../ApkParser.php';      
    /**
    * @todo test! test! test! 
    */
    class Test_Apk_Main extends PHPUnit_Framework_TestCase
    {
      
    }