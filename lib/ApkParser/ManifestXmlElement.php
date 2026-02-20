<?php

namespace ApkParser;

/**
 * This file is part of the Apk Parser package.
 *
 * (c) Tufan Baris Yildirim <tufanbarisyildirim@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * @property mixed application
 */
class ManifestXmlElement extends \SimpleXMLElement
{
    /**
     * @return array
     */
    public function getPermissions($lang = 'en')
    {
        /**
         * @var \ApkParser\ManifestXmlElement
         */
        $permsArray = $this->{'uses-permission'};
        $permissions = $this->loadPermissionMap($lang);
        $perms = array();
        foreach ($permsArray as $perm) {
            $permAttr = get_object_vars($perm);
            $objNotationArray = explode('.', $permAttr['@attributes']['name']);
            $permName = trim(end($objNotationArray));
            $perms[$permName] = array(
                'description' => isset($permissions[$permName]) ? $permissions[$permName]['desc'] : null,

                'flags' => isset($permissions[$permName]) ?
                    $permissions[$permName]['flags']
                    : array(
                        'cost' => false,
                        'warning' => false,
                        'danger' => false,
                    )
            );
        }
        return $perms;
    }

    /**
     * @param string $lang
     * @return array
     */
    private function loadPermissionMap($lang)
    {
        $paths = array(__DIR__ . "/lang/{$lang}.permissions.json");
        if ($lang !== 'en') {
            $paths[] = __DIR__ . "/lang/en.permissions.json";
        }

        foreach ($paths as $path) {
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }
            $json = file_get_contents($path);
            if ($json === false) {
                continue;
            }
            $permissions = json_decode($json, true);
            if (is_array($permissions)) {
                return $permissions;
            }
        }

        return array();
    }

    /**
     * @return array
     */
    public function getPermissionsRaw()
    {
        /**
         * @var \ApkParser\ManifestXmlElement
         */
        $permsArray = $this->{'uses-permission'};
        $perms = array();
        foreach ($permsArray as $perm) {
            $permAttr = get_object_vars($perm);
            $perms[] = $permAttr['@attributes']['name'];
        }
        return $perms;
    }


    /**
     * @return Application
     */
    public function getApplication()
    {
        return new Application($this->application);
    }

    /**
     * @param $attributeName
     * @return null
     */
    public function getAttribute($attributeName)
    {
        $attrs = get_object_vars($this);
        return isset($attrs['@attributes'][$attributeName]) ? $attrs['@attributes'][$attributeName] : null;
    }
}
