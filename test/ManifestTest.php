<?php

/**
 * Created by mcfedr on 1/15/16 12:43
 */
class ManifestTest extends PHPUnit_Framework_TestCase
{

    public function testMetaData()
    {
        $mock = $this->getMockBuilder('ApkParser\XmlParser')
            ->disableOriginalConstructor()
            ->setMethods(array('getXmlString'))
            ->getMock();

        $file = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'meta.xml';
        $mock->expects($this->once())->method('getXmlString')->will($this->returnValue(file_get_contents($file)));

        $manifest = new \ApkParser\Manifest($mock);

        $this->assertEquals('0x7f0c0012', $manifest->getMetaData('com.google.android.gms.version'));
    }
}
