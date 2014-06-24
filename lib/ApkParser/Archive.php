<?php
namespace ApkParser;
/**
 * Customized ZipArchive for .apk files.
 * @author Tufan Baris YILDIRIM
 * @TODO  Add ->getResource('file_name'), or getIcon() directly.
 * @todo Override the // extractTo() method. Rewrite all of XML files converted from Binary Xml to text based XML!
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


    public function __construct($file = false)
    {
        if ($file && is_file($file)) {
            $this->open($file);
            $this->fileName = basename($this->filePath = $file);
        } else
            throw new \Exception($file . " not a regular file");

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
            $xmlParser = new \ApkParser\XmlParser(new Stream($this->getStream($name)));
            return $xmlParser->getXmlString();
        } else
            return parent::getFromName($name, $length, $flags);
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
        return new \ApkParser\Stream($this->getStream('classes.dex'));
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
            //TODO: ApkXmlParser can not parse the main.xml and others! only AndroidManifest.xml
            //return $extResult;

            $xmlFiles = Utils::globRecursive($destination . '/*.xml');


            foreach ($xmlFiles as $xmlFile) {
                // TODO : Remove this ifcheck , if ApkXml can parse! amk!
                if ($xmlFile == ($destination . "/AndroidManifest.xml"))
                    \ApkParser\XmlParser::decompressFile($xmlFile);
            }
        }

        return $extResult;

    }

}
