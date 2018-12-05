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
        $permissions = json_decode(file_get_contents(__DIR__ . "/lang/{$lang}.permissions.json"), true);
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
                        'cost'    => false,
                        'warning' => false,
                        'danger'  => false,
                    )
            );
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
