<?php
namespace ApkParser;

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

    public function __construct(\ApkParser\ManifestXmlElement $application)
    {
        $this->application = $application;

        foreach ($application->activity as $actXml) {
            $this->activities[] = new \ApkParser\Activity($actXml);
        }
    }

    public function getIcon()
    {
        return $this->getAttr('icon');
    }

    public function getLabel()
    {
         return $this->getAttr('label');
    }

    public function getAttr($attrName){
       $attr =   get_object_vars($this->application);
        return (string)$attr['@attributes'][$attrName];
    }

    public function getActivityNameList()
    {
        $names = array();

        foreach ($this->activities as $act)
        {
            $names[] = trim($act->getName(), '.');
        }

        return $names;
    }

    public function getActivityHash()
    {
        return md5(implode('',$this->getActivityNameList()));
    }
}
