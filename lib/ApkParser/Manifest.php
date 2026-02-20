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
class Manifest extends Xml
{
    /**
     * @deprecated Permission metadata now comes from ManifestXmlElement + lang/*.permissions.json.
     * Kept as an empty compatibility shim to avoid breaking static property access.
     * @var array
     */
    public static $permissions = array();

    /**
     * @deprecated Permission metadata now comes from ManifestXmlElement + lang/*.permissions.json.
     * Kept as an empty compatibility shim to avoid breaking static property access.
     * @var array
     */
    public static $permission_flags = array();
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
     * @throws Exceptions\XmlParserException
     */
    public function getApplication()
    {
        return $this->getXmlObject()->getApplication();
    }

    /**
     * get SimleXmlElement created from AndroidManifest.xml
     *
     * @param mixed $className
     * @return ManifestXmlElement|\SimpleXMLElement
     * @throws Exceptions\XmlParserException
     */
    public function getXmlObject($className = '\ApkParser\ManifestXmlElement')
    {
        return $this->xmlParser->getXmlObject($className);
    }

    /**
     * Get Application Permissions
     * @param string $lang
     * @return array
     * @throws Exceptions\XmlParserException
     */
    public function getPermissions($lang = 'en')
    {
        return $this->getXmlObject()->getPermissions($lang);
    }

    /**
     * Get Application Permissions
     * @return array
     * @throws Exceptions\XmlParserException
     */
    public function getPermissionsRaw()
    {
        return $this->getXmlObject()->getPermissionsRaw();
    }

    /**
     * Android Package Name
     * @return string
     * @throws \Exception
     */
    public function getPackageName()
    {
        return $this->getAttribute('package');
    }

    /**
     * @param $attributeName
     * @return mixed
     * @throws \Exception
     */
    private function getAttribute($attributeName)
    {
        if ($this->attrs === null) {
            $xmlObj = $this->getXmlObject();
            $vars = get_object_vars($xmlObj->attributes());
            $this->attrs = $vars['@attributes'];
        }

        if (!isset($this->attrs[$attributeName])) {
            throw new \Exception("Attribute not found : " . $attributeName);
        }

        return $this->attrs[$attributeName];
    }

    /**
     * Application Version Name
     * @return string
     * @throws \Exception
     */
    public function getVersionName()
    {
        return $this->getAttribute('versionName');
    }

    /**
     * Application Version Code
     * @return mixed
     * @throws \Exception
     */
    public function getVersionCode()
    {
        return hexdec($this->getAttribute('versionCode'));
    }

    /**
     * @return bool
     * @throws Exceptions\XmlParserException
     */
    public function isDebuggable()
    {
        $application = $this->getApplication();
        $debuggable = $application->getAttr('debuggable');

        // Keep compatibility for manifests where debuggable is exposed on root attrs.
        if ($debuggable === null && $this->attrs !== null && array_key_exists('debuggable', $this->attrs)) {
            $debuggable = $this->attrs['debuggable'];
        }

        if ($debuggable === null) {
            return false;
        }

        return $this->parseBooleanValue($debuggable);
    }

    /**
     * Parse Android boolean-ish attribute values safely.
     *
     * @param mixed $value
     * @return bool
     */
    private function parseBooleanValue($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return ((int)$value) !== 0;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return false;
            }
            if (preg_match('/^0x[0-9a-f]+$/i', $value) === 1) {
                return hexdec($value) !== 0;
            }
            if (is_numeric($value)) {
                return ((float)$value) !== 0.0;
            }

            $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool !== null) {
                return $bool;
            }
        }

        return (bool)$value;
    }

    /**
     * @param $name
     * @return mixed|null
     * @throws Exceptions\XmlParserException
     */
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
        return array_key_exists($name, $this->meta) ? $this->meta[$name] : null;
    }

    /**
     * More Information About The minimum API Level required for the application to run.
     * @return AndroidPlatform
     * @throws \Exception
     */
    public function getMinSdk()
    {
        return new AndroidPlatform($this->getMinSdkLevel());
    }

    /**
     * The minimum API Level required for the application to run.
     * @return int
     * @throws Exceptions\XmlParserException
     */
    public function getMinSdkLevel()
    {
        $xmlObj = $this->getXmlObject();
        $usesSdk = get_object_vars($xmlObj->{'uses-sdk'});
        if (isset($usesSdk['@attributes']) && isset($usesSdk['@attributes']['minSdkVersion'])) {
            return hexdec($usesSdk['@attributes']['minSdkVersion']);
        }
        return null;
    }

    /**
     * More Information About The target API Level required for the application to run.
     * @return AndroidPlatform
     * @throws \Exception
     */
    public function getTargetSdk()
    {
        if ($this->getTargetSdkLevel()) {
            return new AndroidPlatform($this->getTargetSdkLevel());
        }
        return null;
    }

    /**
     * The target API Level required for the application to run.
     * @return float|int
     * @throws Exceptions\XmlParserException
     */
    public function getTargetSdkLevel()
    {
        $xmlObj = $this->getXmlObject();
        $usesSdk = get_object_vars($xmlObj->{'uses-sdk'});
        if (isset($usesSdk['@attributes']) && isset($usesSdk['@attributes']['targetSdkVersion'])) {
            return hexdec($usesSdk['@attributes']['targetSdkVersion']);
        }
        return null;
    }

    /**
     * Basically string casting method.
     * @throws \Exception
     */
    public function __toString()
    {
        return $this->getXmlString();
    }

    /**
     * Returns ManifestXml as a String.
     * @return string
     * @throws \Exception
     */
    public function getXmlString()
    {
        return $this->xmlParser->getXmlString();
    }
}
