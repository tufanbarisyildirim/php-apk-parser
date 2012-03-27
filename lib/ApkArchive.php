<?php
    include_once dirname(__FILE__) . "/ApkStream.php";

    /**
    * Customized ZipArchive for .apk files.
    * @author Tufan Baris YILDIRIM 
    * @TODO  Add ->getResource('file_name'), or getIcon() directly.
    * @todo Override the // extractTo() method. Rewrite all of XML files converted from Binary Xml to text based XML!
    */
    class ApkArchive extends ZipArchive
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
            if($file && is_file($file))
            {
                $this->open($file);
                $this->fileName = basename($this->filePath = $file);
            }
            else
                throw new Exception($file . " not a regular file");

        }                 

        /**
        * Returns an ApkStream whick contains AndroidManifest.xml
        * @return ApkStream
        */
        public function getManifestStream()
        {
            return new ApkStream($this->getStream('AndroidManifest.xml'));
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

    }
