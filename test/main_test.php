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
                if(assert($exp))
                {
                    echo "<span style=\"color:green\">Passed ... </span>\r\n";
                }   
                else
                {
                    echo "<span style=\"color:red\">Failed ... </span>\r\n";
                }
            }

            public function assertHasIndex(array $array, $index)
            {
                return  $this->assertTrue(isset($array[$index]));
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
            $this->assertHasIndex($permissionArray,'INTERNET');
            $this->assertHasIndex($permissionArray,'CAMERA');
            $this->assertHasIndex($permissionArray,'BLUETOOTH');
            $this->assertHasIndex($permissionArray,'BLUETOOTH_ADMIN');
        }
    }

    echo '<pre>';
    $test = new Test_Apk_Main();
    $test->TestPermissions();