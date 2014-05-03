<?php

use ApkParser\Parser;

class ParserTest extends PHPUnit_Framework_TestCase {
    /**
     * @var ApkParser\Parser
     */
    private $subject;

    public function setUp() {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'EBHS.apk';
        $this->subject = new Parser($file);
    }

    public function testSanity() {
        $this->assertTrue(true);
    }

    public function testPermissions() {
        $permissions = $this->subject->getManifest()->getPermissions();

        $this->assertEquals(count($permissions), 4);
        $this->assertArrayHasKey('INTERNET', $permissions, "INTERNET permission not found!");
        $this->assertArrayHasKey('CAMERA', $permissions, "CAMERA permission not found!");
        $this->assertArrayHasKey('BLUETOOTH', $permissions, "BLUETOOTH permission not found!");
        $this->assertArrayHasKey('BLUETOOTH_ADMIN', $permissions, "BLUETOOTH_ADMIN permission not found!");
    }
}
