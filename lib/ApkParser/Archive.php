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
     * @param bool|string $file
     * @throws \Exception
     */
    public function __construct(string|bool $file = false)
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
     * @param int|null $length
     * @param int|null $flags
     * @return string|false
     * @throws \Exception
     */
    public function getFromName(string $name, ?int $length = null, ?int $flags = null): string|false
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
    public function getManifestStream(): Stream
    {
        return new Stream($this->getStream('AndroidManifest.xml'));
    }

    /**
     * @return SeekableStream
     */
    public function getResourcesStream(): SeekableStream
    {
        return new SeekableStream($this->getStream('resources.arsc'));
    }

    /**
     * Returns an \ApkParser\Stream instance which contains classes.dex file
     * @returns Stream
     * @throws \Exception
     */
    public function getClassesDexStream(): Stream
    {
        return new Stream($this->getStream('classes.dex'));
    }

    /**
     * Apk file path.
     * @return bool|string
     */
    public function getApkPath(): bool|string
    {
        return $this->filePath;
    }

    /**
     * Apk file name
     * @return string
     */
    public function getApkName(): string
    {
        return $this->fileName;
    }


    /**
     * @param string $pathto
     * @param array|string|null $files
     * @return bool
     * @throws \Exception
     */
    public function extractTo(string $pathto, array|string|null $files = null): bool
    {
        if ($extResult = parent::extractTo($pathto, $files)) {
            $xmlFiles = Utils::globRecursive($pathto . '/*.xml');

            foreach ($xmlFiles as $xmlFile) {
                if ($xmlFile == ($pathto . "/AndroidManifest.xml")) {
                    XmlParser::decompressFile($xmlFile);
                }
            }
        }

        return $extResult;
    }
}
