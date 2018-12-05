<?php

namespace ApkParser\Exceptions;


/**
 * This file is part of the Apk Parser package.
 *
 * (c) Tufan Baris Yildirim <tufanbarisyildirim@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class XmlParserException extends ApkException
{
    /**
     * @var \LibXMLError[]
     */
    private $xmlErrors;

    /**
     * XmlParserException constructor.
     * @param $xmlstr
     */
    public function __construct($xmlstr)
    {
        $this->xmlErrors = libxml_get_errors();
        $xml = explode("\n", $xmlstr);
        $message = "";
        foreach ($this->xmlErrors as $error) {
            $message .= $this->display_xml_error($error, $xml);
        }

        libxml_clear_errors();

        parent::__construct($message);
    }

    /**
     * Borrowed from http://php.net/manual/en/function.libxml-get-errors.php
     *
     * @param \LibXMLError $error
     * @param string $xml
     * @return string
     */
    private function display_xml_error(\LibXMLError $error, $xml)
    {
        $return = $xml[$error->line - 1] . "\n";
        $return .= str_repeat('-', $error->column) . "^\n";

        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= "Warning $error->code: ";
                break;
            case LIBXML_ERR_ERROR:
                $return .= "Error $error->code: ";
                break;
            case LIBXML_ERR_FATAL:
                $return .= "Fatal Error $error->code: ";
                break;
        }

        $return .= trim($error->message) .
            "\n  Line: $error->line" .
            "\n  Column: $error->column";

        if ($error->file) {
            $return .= "\n  File: $error->file";
        }

        return "$return\n";
    }

}
