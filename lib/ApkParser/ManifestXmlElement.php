<?php
    namespace ApkParser;

    class ManifestXmlElement extends \SimpleXMLElement
    {
        public function getPermissions()
        {
            /**
            * @var \ApkParser\ManifestXmlElement
            */
            $permsArray = $this->{'uses-permission'};

            $perms = array();
            foreach($permsArray as $perm)
            {
                $permAttr = get_object_vars($perm);  
                $objNotationArray = explode('.',$permAttr['@attributes']['name']);
                $permName = trim(end($objNotationArray));
                $perms[$permName] =  \ApkParser\Manifest::$permissions[$permName];
            }

            return $perms;
        }
    }
