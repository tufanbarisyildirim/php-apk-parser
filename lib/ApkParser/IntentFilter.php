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

class IntentFilter
{
    public $actions;
    public $categories;


    /**
     * @param ManifestXmlElement $filterXml
     */
    public function __construct(ManifestXmlElement $filterXml)
    {
        $filterArray = get_object_vars($filterXml);


        if (isset($filterArray['action'])) {

            if (!is_array($filterArray['action'])) {
                $filterArray['action'] = array($filterArray['action']);
            }

            foreach ($filterArray['action'] as $act) {
                $actionElement = get_object_vars($act);
                $actionNameSections = explode('.', $actionElement['@attributes']['name']);
                $this->actions[] = end($actionNameSections);
            }
        }

        if (isset($filterArray['category'])) {

            if (!is_array($filterArray['category'])) {
                $filterArray['category'] = array($filterArray['category']);
            }


            foreach ($filterArray['category'] as $cat) {
                $categoryElement = get_object_vars($cat);
                $categoryNameSections = explode('.', $categoryElement['@attributes']['name']);
                $this->categories[] = end($categoryNameSections);
            }
        }


    }

    /**
     * @return mixed
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param mixed $actions
     */
    public function setActions($actions)
    {
        $this->actions = $actions;
    }

    /**
     * @return mixed
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param mixed $categories
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    }

}