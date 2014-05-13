<?php
   namespace ApkParser;

    /**
    * @author Tufan Baris YILDIRIM
    * @version v0.1
    * @since 27.03.2012
    * @link https://github.com/tufanbarisyildirim/php-apk-parser
    *
    * Main Class.
    * - Set the apk path on construction,
    * - Get the Manifest object.
    * - Print the Manifest XML.
    *
    * @property $apk \ApkParser\Archive
    * @property $manifest \ApkParser\Manifest
    * @property $resources \ApkParser\ResourcesParser
    */
    class Parser
    {
        private $apk;
        private $manifest;
        private $resources;

        public function __construct($apkFile)
        {
            $this->apk = new Archive($apkFile);
            $this->manifest = new Manifest(new XmlParser($this->apk->getManifestStream()));
            $this->resources = new ResourcesParser($this->apk->getResourcesStream());
        }

        /**
        * Get Manifest Object
        * @return \ApkParser\Manifest
        */
        public function getManifest()
        {
            return $this->manifest;
        }

        /**
        * Get the apk. Zip handler.
        * - Extract all(or sp. entries) files,
        * - add file,
        * - recompress
        * - and other ZipArchive features.
        *
        * @return \ApkParser\Archive
        */
        public function getApkArchive()
        {
            return $this->apk;
        }

        public function getResources($key)
        {
            return $this->resources->getResources($key);
        }

        public function getStream($name)
        {
            return $this->apk->getStream($name);
        }

        /**
        * Extract apk content directly
        *
        * @param mixed $destination
        * @param array $entries
        * @return bool
        */
        public function extractTo($destination,$entries = NULL)
        {
             return $this->apk->extractTo($destination,$entries);
        }
}
