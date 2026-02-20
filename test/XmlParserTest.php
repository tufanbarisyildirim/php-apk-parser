<?php

/**
 * Created by mcfedr on 1/15/16 12:14
 */
class XmlParserTest extends \PHPUnit\Framework\TestCase
{
    public function testXmlObject()
    {
        $mock = $this->getMockBuilder(\ApkParser\XmlParser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('getXmlString'))
            ->getMock();

        $file = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'invalid.xml';
        $mock->expects($this->once())->method('getXmlString')->willReturn(file_get_contents($file));

        $this->expectException(\ApkParser\Exceptions\XmlParserException::class);

        $mock->getXmlObject();
    }

    public function testXmlObjectRestoresLibxmlErrorModeOnFailure()
    {
        $mock = $this->getMockBuilder(\ApkParser\XmlParser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array('getXmlString'))
            ->getMock();

        $file = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'invalid.xml';
        $mock->expects($this->once())->method('getXmlString')->willReturn(file_get_contents($file));

        $originalMode = libxml_use_internal_errors(false);
        try {
            try {
                $mock->getXmlObject();
                $this->fail('Expected XmlParserException was not thrown');
            } catch (\ApkParser\Exceptions\XmlParserException $e) {
                // Expected
            }

            $previousMode = libxml_use_internal_errors(true);
            $this->assertFalse($previousMode);
            libxml_use_internal_errors(false);
        } finally {
            libxml_use_internal_errors($originalMode);
        }
    }

    public function testDecompressFileFromApkManifest()
    {
        $apkFile = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'EBHS.apk';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($apkFile) === true);

        $manifestBinary = $zip->getFromName('AndroidManifest.xml');
        $zip->close();

        $this->assertTrue(is_string($manifestBinary));

        $sourceFile = tempnam(sys_get_temp_dir(), 'apk-manifest-src-');
        $destinationFile = tempnam(sys_get_temp_dir(), 'apk-manifest-dst-');
        $this->assertTrue(is_string($sourceFile));
        $this->assertTrue(is_string($destinationFile));

        try {
            file_put_contents($sourceFile, $manifestBinary);
            \ApkParser\XmlParser::decompressFile($sourceFile, $destinationFile);

            $xml = file_get_contents($destinationFile);
            $this->assertTrue(is_string($xml));
            $this->assertStringContainsString('<manifest', $xml);
        } finally {
            if (is_string($sourceFile) && file_exists($sourceFile)) {
                unlink($sourceFile);
            }
            if (is_string($destinationFile) && file_exists($destinationFile)) {
                unlink($destinationFile);
            }
        }
    }
}
