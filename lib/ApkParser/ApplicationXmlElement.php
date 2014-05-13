<?php

namespace ApkParser;

class ApplicationXmlElement {
    /**
     * @var \SimpleXMLElement
     */
    private $xml;

    function __construct(\SimpleXMLElement $xml) {
        $this->xml = $xml;
    }

    public function getIcon() {
        return (string) $this->xml['icon'];
    }

    public function getLabel() {
        return (string) $this->xml['label'];
    }
}
