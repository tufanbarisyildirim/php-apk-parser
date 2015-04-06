<?php
namespace ApkParser;

/**
 * This file is part of the Apk Parser package.
 *
 * (c) Tufan Baris Yildirim <tufanbarisyildirim@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Archive extends \ZipArchive
{
    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $fileName;


    /**
     * @param bool $file
     * @throws \Exception
     */
    public function __construct($file = false)
    {
        if ($file && is_file($file)) {
            $this->open($file);
            $this->fileName = basename($this->filePath = $file);
        } else {
            throw new \Exception($file . " not a regular file");
        }

    }

    /**
     * Get a file from apk Archive by name.
     *
     * @param string $name
     * @param int $length
     * @param int $flags
     * @return mixed
     */
    public function getFromName($name, $length = NULL, $flags = NULL)
    {
        if (strtolower(substr($name, -4)) == '.xml') {
            $xmlParser = new XmlParser(new Stream($this->getStream($name)));
            return $xmlParser->getXmlString();
        } else {
            return parent::getFromName($name, $length, $flags);
        }
    }

    /**
     * Returns an ApkStream which contains AndroidManifest.xml
     * @return Stream
     */
    public function getManifestStream()
    {
        return new Stream($this->getStream('AndroidManifest.xml'));
    }

    /**
     * @return SeekableStream
     */
    public function getResourcesStream()
    {
        return new SeekableStream($this->getStream('resources.arsc'));
    }

    /**
     * Returns an \ApkParser\Stream instance which contains classes.dex file
     * @returns \ApkParser\Stream
     */
    public function getClassesDexStream()
    {
        return new Stream($this->getStream('classes.dex'));
    }

    /**
     * Apk file path.
     * @return string
     */
    public function getApkPath()
    {
        return $this->filePath;
    }

    /**
     * Apk file name
     * @return string
     */
    public function getApkName()
    {
        return $this->fileName;
    }


    public function extractTo($destination, $entries = NULL)
    {
        if ($extResult = parent::extractTo($destination, $entries)) {

            $xmlFiles = Utils::globRecursive($destination . '/*.xml');

            foreach ($xmlFiles as $xmlFile) {
                if ($xmlFile == ($destination . "/AndroidManifest.xml")) {
                    XmlParser::decompressFile($xmlFile);
                }
            }
        }

        return $extResult;

    }

}
