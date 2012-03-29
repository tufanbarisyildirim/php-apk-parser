<?php
    //I used only "Equal" assertation so you can test without PHPUnit Framework.
    if(!class_exists('PHPUnit_Framework_TestCase'))
    {
        class PHPUnit_Framework_TestCase
        {
            public function assertEquals($a,$b)
            {
                return $this->assertTrue($a === $b);
            } 

            public function assertTrue($exp)
            {
                
                return assert($exp);
            }

            public function assertArrayHasKey($key, array $array,$message = '')
            {
                  $this->assertTrue(isset($array[$key]));
            } 
        }
    }



    include '../ApkParser.php';      
    /**
    * @todo test! test! test! 
    */
    class Test_Apk_Main extends PHPUnit_Framework_TestCase
    {
        /**
        We have 4 permissions in EBHS.apk/AndroidManifest.xml
        INTERNET
        CAMERA
        BLUETOOTH
        BLUETOOTH_ADMIN
        */
        public function TestPermissions()
        {
            $apk = new ApkParser('../examples/EBHS.apk');
            $permissionArray = $apk->getManifest()->getPermissions();
            $this->assertEquals(count($permissionArray),4);
            $this->assertArrayHasKey('INTERNET',$permissionArray,"INTERNET permission not found!");
            $this->assertArrayHasKey('CAMERA',$permissionArray,"CAMERA permission not found!");
            $this->assertArrayHasKey('BLUETOOTH',$permissionArray,"BLUETOOTH permission not found!");
            $this->assertArrayHasKey('BLUETOOTH_ADMIN',$permissionArray,"BLUETOOTH_ADMIN permission not found!");
        }
    }

    echo '<pre>';
    $test = new Test_Apk_Main();
    $test->TestPermissions();