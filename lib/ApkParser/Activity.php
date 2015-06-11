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

class Activity
{
    public $label;
    public $name;

    /**
     * @var \ApkParser\IntentFilter[] $filters
     */
    public $filters = array();


    public $isLauncher = false;


    /**
     * @param ManifestXmlElement $actXml
     */
    public function __construct(ManifestXmlElement $actXml)
    {

        $actArray = get_object_vars($actXml);
        $attrs = $actArray['@attributes'];
        $this->setName(isset($attrs['name']) ? $attrs['name'] : null);
        $this->setLabel(isset($attrs['label']) ? $attrs['label'] : null);

        if (isset($actArray['intent-filter'])) {
            if (!is_array($actArray['intent-filter'])) {
                $actArray['intent-filter'] = array($actArray['intent-filter']);
            }

            foreach ($actArray['intent-filter'] as $filterXml) {
                $this->filters[] = new IntentFilter($filterXml);
            }
        }

        foreach ($this->filters as $filter) {
            if (($filter->actions != null && in_array('MAIN', $filter->actions)) &&
                ($filter->categories != null && in_array('LAUNCHER', $filter->categories))
            ) {
                $this->isLauncher = true;
            }
        }

    }

    /**
     * @param $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param array $filters
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
    }

    /**
     * @return IntentFilter[]
     */
    public function getFilters()
    {
        return $this->filters; // we may need an intent-filter class
    }

    /**
     * @return boolean
     */
    public function isLauncher()
    {
        return $this->isLauncher;
    }

    /**
     * @param boolean $isLauncher
     */
    public function setIsLauncher($isLauncher)
    {
        $this->isLauncher = $isLauncher;
    }

    /**
     * @return boolean
     */
    public function isIsLauncher()
    {
        return $this->isLauncher;
    }
}
