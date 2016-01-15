<?php

/**
 * Created by mcfedr on 1/15/16 12:14
 */
class XmlParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException ApkParser\Exceptions\XmlParserException
     */
    public function testXmlObject()
    {
        $mock = $this->getMockBuilder('ApkParser\XmlParser')
            ->disableOriginalConstructor()
            ->setMethods(array('getXmlString'))
            ->getMock();

        $file = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'invalid.xml';
        $mock->expects($this->once())->method('getXmlString')->will($this->returnValue(file_get_contents($file)));

        $mock->getXmlObject();
    }
}
