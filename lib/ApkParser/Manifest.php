<?php
namespace ApkParser;

use ApkParser\AndroidPlatform;

/**
 * This file is part of the Apk Parser package.
 *
 * (c) Tufan Baris Yildirim <tufanbarisyildirim@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Manifest extends \ApkParser\Xml
{

    private $xmlParser;
    private $attrs = null;
    private $meta = null;

    /**
     * @param XmlParser $xmlParser
     */
    public function __construct(XmlParser $xmlParser)
    {
        $this->xmlParser = $xmlParser;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->getXmlObject()->getApplication();
    }

    /**
     * Returns ManifestXml as a String.
     * @return string
     */
    public function getXmlString()
    {
        return $this->xmlParser->getXmlString();
    }

    /**
     * Get Application Permissions
     * @return array
     */
    public function getPermissions($lang = 'en')
    {
        return $this->getXmlObject()->getPermissions($lang);
    }

//    public function getPermission($permName)
//    {
//
//    }
    /**
     * Android Package Name
     * @return string
     */
    public function getPackageName()
    {
        return $this->getAttribute('package');
    }

    /**
     * Application Version Name
     * @return string
     */
    public function getVersionName()
    {
        return $this->getAttribute('versionName');
    }

    /**
     * Application Version Code
     * @return mixed
     */
    public function getVersionCode()
    {
        return hexdec($this->getAttribute('versionCode'));
    }

    /**
     * @return bool
     */
    public function isDebuggable()
    {
        return (bool)$this->getAttribute('debuggable');
    }

    /**
     * The minimum API Level required for the application to run.
     * @return int
     */
    public function getMinSdkLevel()
    {
        $xmlObj = $this->getXmlObject();
        $usesSdk = get_object_vars($xmlObj->{'uses-sdk'});
        return hexdec($usesSdk['@attributes']['minSdkVersion']);
    }

    private function getAttribute($attributeName)
    {
        if ($this->attrs === NULL) {
            $xmlObj = $this->getXmlObject();
            $vars = get_object_vars($xmlObj->attributes());
            $this->attrs = $vars['@attributes'];
        }

        if (!isset($this->attrs[$attributeName]))
            throw new \Exception("Attribute not found : " . $attributeName);

        return $this->attrs[$attributeName];
    }

    public function getMetaData($name)
    {
        if ($this->meta === null) {
            $xmlObj = $this->getXmlObject();
            $nodes = $xmlObj->xpath('//meta-data');
            $this->meta = array();

            foreach ($nodes as $node) {
                $nodeAttrs = get_object_vars($node->attributes());
                $nodeName = $nodeAttrs['@attributes']['name'];
                if (array_key_exists('value', $nodeAttrs['@attributes'])) {
                    $this->meta[$nodeName] = $nodeAttrs['@attributes']['value'];
                } elseif (array_key_exists('resource', $nodeAttrs['@attributes'])) {
                    $this->meta[$nodeName] = $nodeAttrs['@attributes']['resource'];
                }
            }
        }
        return $this->meta[$name];
    }

    /**
     * More Information About The minimum API Level required for the application to run.
     * @return AndroidPlatform
     */
    public function getMinSdk()
    {
        return new AndroidPlatform($this->getMinSdkLevel());
    }

    /**
     * get SimleXmlElement created from AndroidManifest.xml
     *
     * @param mixed $className
     * @return \ApkParser\ManifestXmlElement
     */
    public function getXmlObject($className = '\ApkParser\ManifestXmlElement')
    {
        return $this->xmlParser->getXmlObject($className);
    }

    /**
     * Basically string casting method.
     */
    public function __toString()
    {
        return $this->getXmlString();
    }
}