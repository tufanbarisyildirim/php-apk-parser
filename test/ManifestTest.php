<?php

/**
 * Created by mcfedr on 1/15/16 12:43
 */
class ManifestTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws \ApkParser\Exceptions\XmlParserException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testMetaData()
    {
        $mock = $this->getMockBuilder(\ApkParser\XmlParser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('getXmlString'))
            ->getMock();

        $file = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'meta.xml';
        $mock->expects($this->once())->method('getXmlString')->willReturn(file_get_contents($file));

        $manifest = new \ApkParser\Manifest($mock);

        $this->assertEquals('0x7f0c0012', $manifest->getMetaData('com.google.android.gms.version'));
    }

    /**
     * @throws \ApkParser\Exceptions\XmlParserException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testMissingMetaDataReturnsNull()
    {
        $manifest = $this->createManifestFromFile('meta.xml');

        $this->assertNull($manifest->getMetaData('not.existing.meta.key'));
    }

    /**
     * @throws \ApkParser\Exceptions\XmlParserException
     */
    public function testIsDebuggableHexZeroReturnsFalse()
    {
        $manifest = $this->createManifestFromXmlString(
            '<?xml version="1.0" encoding="UTF-8"?><manifest package="com.example.app"><application debuggable="0x0"></application></manifest>'
        );

        $this->assertFalse($manifest->isDebuggable());
    }

    /**
     * @throws \ApkParser\Exceptions\XmlParserException
     */
    public function testIsDebuggableHexOneReturnsTrue()
    {
        $manifest = $this->createManifestFromXmlString(
            '<?xml version="1.0" encoding="UTF-8"?><manifest package="com.example.app"><application debuggable="0x1"></application></manifest>'
        );

        $this->assertTrue($manifest->isDebuggable());
    }

    /**
     * @throws \ApkParser\Exceptions\XmlParserException
     */
    public function testIsDebuggableMissingReturnsFalse()
    {
        $manifest = $this->createManifestFromXmlString(
            '<?xml version="1.0" encoding="UTF-8"?><manifest package="com.example.app"><application></application></manifest>'
        );

        $this->assertFalse($manifest->isDebuggable());
    }

    /**
     * @throws \ApkParser\Exceptions\XmlParserException
     */
    public function testPermissionsFallbackToEnglishWhenLanguageFileMissing()
    {
        $manifest = $this->createManifestFromFile('meta.xml');
        $permissions = $manifest->getPermissions('xx-does-not-exist');

        $this->assertArrayHasKey('INTERNET', $permissions);
        $this->assertNotNull($permissions['INTERNET']['description']);
    }

    /**
     * @param string $file
     * @return \ApkParser\Manifest
     */
    private function createManifestFromFile($file)
    {
        return $this->createManifestFromXmlString(
            file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $file)
        );
    }

    /**
     * @param string $xml
     * @return \ApkParser\Manifest
     */
    private function createManifestFromXmlString($xml)
    {
        $mock = $this->getMockBuilder(\ApkParser\XmlParser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('getXmlString'))
            ->getMock();
        $mock->expects($this->once())->method('getXmlString')->willReturn($xml);

        return new \ApkParser\Manifest($mock);
    }
}
