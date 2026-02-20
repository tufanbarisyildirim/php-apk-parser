<?php

use ApkParser\Config;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testGetReturnsConfiguredValues()
    {
        $config = new Config(array(
            'manifest_only' => false,
            'java_binary' => '/usr/bin/java-custom',
        ));

        $this->assertFalse($config->get('manifest_only'));
        $this->assertSame('/usr/bin/java-custom', $config->get('java_binary'));
    }

    public function testGetReturnsNullForUnknownKey()
    {
        $config = new Config();

        $this->assertNull($config->get('unknown_key'));
    }

    public function testMagicGetReturnsNullForUnknownKey()
    {
        $config = new Config();

        $this->assertNull($config->unknown_key);
    }
}
