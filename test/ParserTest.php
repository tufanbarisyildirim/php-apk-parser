<?php
/**
 * This file is part of the Apk Parser package.
 *
 * (c) Tufan Baris Yildirim <tufanbarisyildirim@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use ApkParser\Parser;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ApkParser\Parser
     */
    private $subject;

    /**
     * @throws Exception
     */
    public function setUp()
    {
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'EBHS.apk';
        $this->subject = new Parser($file);
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testSanity()
    {
        $this->assertTrue(true);
    }

    /**
     * @throws \ApkParser\Exceptions\XmlParserException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testPermissions()
    {
        $permissions = $this->subject->getManifest()->getPermissions();

        $this->assertEquals(count($permissions), 4);
        $this->assertArrayHasKey('INTERNET', $permissions, "INTERNET permission not found!");
        $this->assertArrayHasKey('CAMERA', $permissions, "CAMERA permission not found!");
        $this->assertArrayHasKey('BLUETOOTH', $permissions, "BLUETOOTH permission not found!");
        $this->assertArrayHasKey('BLUETOOTH_ADMIN', $permissions, "BLUETOOTH_ADMIN permission not found!");
    }

    /**
     * @throws \ApkParser\Exceptions\XmlParserException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testApplication()
    {
        $application = $this->subject->getManifest()->getApplication();

        $this->assertInstanceOf('ApkParser\Application', $application);
        $this->assertEquals($application->getIcon(), '0x7f020001');
        $this->assertEquals($application->getLabel(), '0x7f050001');
    }

    /**
     * @throws \ApkParser\Exceptions\XmlParserException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testIconResources()
    {
        $application = $this->subject->getManifest()->getApplication();
        $resources = $this->subject->getResources($application->getIcon());

        $expected = array('res/drawable-ldpi/ebhs.png', 'res/drawable-mdpi/ebhs.png', 'res/drawable-hdpi/ebhs.png');
        $this->assertEquals($resources, $expected);
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testIconStream()
    {
        $stream = $this->subject->getStream('res/drawable-hdpi/ebhs.png');
        $icon = stream_get_contents($stream);
        $file = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'ebhs.png';
        $expected = file_get_contents($file);

        $this->assertTrue(is_resource($stream));
        $this->assertEquals(base64_encode($icon), base64_encode($expected));
    }

    /**
     * @throws \ApkParser\Exceptions\XmlParserException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testLabelResources()
    {
        $application = $this->subject->getManifest()->getApplication();
        $resources = $this->subject->getResources($application->getLabel());

        $expected = array('EBHS');
        $this->assertEquals($resources, $expected);
    }
}
