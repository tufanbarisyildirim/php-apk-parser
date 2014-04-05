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
    */
    class Parser
    {
        private $apk;
        private $manifest;

        public function __construct($apkFile)
        {
            $this->apk      = new \ApkParser\Archive($apkFile);
            $this->manifest = new \ApkParser\Manifest(new \ApkParser\XmlParser($this->apk->getManifestStream()));
        }

        /**
        * Get Manifest Object
        * @return \ApkParser\Manifest
        */
        public function getManifest()
        {
            return $this->manifest;
        }

        public function getIcon($wantedResolution = null) {
            
            $icon = null;

            foreach(\ApkParser\AndroidPlatform::$resolutions as $id => $resolution) {
                $stream = $this->apk->getFromName("res/drawable-{$resolution}/ic_launcher.png");

                if($wantedResolution != null && $wantedResolution == $resolution) {
                    return $stream;
                }

                if($stream !== false) {
                    $icon = $stream;
                }           
            }

            return $icon;
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
