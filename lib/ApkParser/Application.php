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

class Application
{
    /**
     * @var \ApkParser\Activity[]
     */
    public $activities = array();

    /**
     * @var ManifestXmlElement
     */
    private $application;

    public function __construct(ManifestXmlElement $application)
    {
        $this->application = $application;

        foreach ($application->activity as $actXml) {
            $this->activities[] = new Activity($actXml);
        }
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->getAttr('icon');
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->getAttr('label');
    }

    /**
     * @param $attrName
     * @return string
     */
    public function getAttr($attrName)
    {
        $attr = get_object_vars($this->application);
        return (string)$attr['@attributes'][$attrName];
    }

    /**
     * @return array
     */
    public function getActivityNameList()
    {
        $names = array();

        foreach ($this->activities as $act) {
            $names[] = trim($act->getName(), '.');
        }

        return $names;
    }

    /**
     * @return string
     */
    public function getActivityHash()
    {
        return md5(implode('', $this->getActivityNameList()));
    }
}
