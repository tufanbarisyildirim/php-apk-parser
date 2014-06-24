<?php
namespace ApkParser;

class Activity
{
    private $label;
    private $name;
    private $filters;

    public function __construct(\ApkParser\ManifestXmlElement $actXml)
    {

        $actArray = get_object_vars($actXml);
        $attrs = $actArray['@attributes'];

        $this->setName(isset($attrs['name']) ? $attrs['name'] : null);
        $this->setLabel(isset($attrs['label']) ? $attrs['label'] : null);
    }

    public function setLabel($label)
    {

        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    public function getFilters()
    {
        return $this->filters; // we may need an intent-filter class
    }
}
