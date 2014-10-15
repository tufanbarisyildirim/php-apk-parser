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


    public function __construct(\ApkParser\ManifestXmlElement $filterXml)
    {
        $filterarray = get_object_vars($filterXml);


        if (isset($filterarray['action'])) {

            if (!is_array($filterarray['action'])) {
                $filterarray['action'] = array($filterarray['action']);
            }

            foreach ($filterarray['action'] as $act) {
                $actionElement = get_object_vars($act);
                $actionNameSections = explode('.', $actionElement['@attributes']['name']);
                $this->actions[] = end($actionNameSections);
            }
        }

        if (isset($filterarray['category'])) {

            if (!is_array($filterarray['category'])) {
                $filterarray['category'] = array($filterarray['category']);
            }


            foreach ($filterarray['category'] as $cat) {
                $categoryElement = get_object_vars($cat);
                $categoryNameSections = explode('.', $categoryElement['@attributes']['name']);
                $this->categories[] = end($categoryNameSections);
            }
        }


    }

}