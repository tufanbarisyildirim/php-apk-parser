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


    public function __construct(\ApkParser\ManifestXmlElement $actXml)
    {

        $actarray = get_object_vars($actXml);
        $attrs = $actarray['@attributes'];
        $this->setName(isset($attrs['name']) ? $attrs['name'] : null);
        $this->setLabel(isset($attrs['label']) ? $attrs['label'] : null);

        if (isset($actarray['intent-filter'])) {
            if (!is_array($actarray['intent-filter']))
                $actarray['intent-filter'] = array($actarray['intent-filter']);

            foreach ($actarray['intent-filter'] as $filterXml) {
                $this->filters[] = new IntentFilter($filterXml);
            }
        }

        foreach ($this->filters as $filter) {
            if (in_array('MAIN', $filter->actions) && in_array('LAUNCHER', $filter->categories))
                $this->isLauncher = true;
        }

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
