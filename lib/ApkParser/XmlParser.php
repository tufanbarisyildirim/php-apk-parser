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
class XmlParser
{
    const END_DOC_TAG = 0x00100101;
    const START_TAG = 0x00100102;
    const END_TAG = 0x00100103;
    const TEXT_TAG = 0x00100104;

    private $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n";
    private $bytes = array();
    private $ready = false;

    public static $indent_spaces = "                                             ";

    /**
     * Store the SimpleXmlElement object
     * @var \SimpleXmlElement
     */
    private $xmlObject = NULL;


    /**
     * @param Stream $apkStream
     */
    public function __construct(Stream $apkStream)
    {
        $this->bytes = $apkStream->getByteArray();
    }

    /**
     * @param $file
     * @param null $destination
     * @throws \Exception
     */
    public static function decompressFile($file, $destination = NULL)
    {
        if (!is_file($file)) {
            throw new \Exception("{$file} is not a regular file");
        }

        $parser = new self(new Stream(fopen($file, 'rd')));
        //TODO : write a method in this class, ->saveToFile();
        file_put_contents($destination === NULL ? $file : $destination, $parser->getXmlString());
    }

    /**
     * @throws \Exception
     */
    public function decompress()
    {
        $numbStrings = $this->littleEndianWord($this->bytes, 4 * 4);
        $sitOff = 0x24;
        $stOff = $sitOff + $numbStrings * 4;
        $this->bytesTagOff = $this->littleEndianWord($this->bytes, 3 * 4);

        for ($ii = $this->bytesTagOff; $ii < count($this->bytes) - 4; $ii += 4) {
            if ($this->littleEndianWord($this->bytes, $ii) == self::START_TAG) {
                $this->bytesTagOff = $ii;
                break;
            }
        }


        $off = $this->bytesTagOff;
        $indentCount = 0;
        $startTagLineNo = -2;

        while ($off < count($this->bytes)) {
            $currentTag = $this->littleEndianWord($this->bytes, $off);
            $lineNo = $this->littleEndianWord($this->bytes, $off + 2 * 4);
            $nameNsSi = $this->littleEndianWord($this->bytes, $off + 4 * 4);
            $nameSi = $this->littleEndianWord($this->bytes, $off + 5 * 4);

            switch ($currentTag) {
                case self::START_TAG: {
                    $tagSix = $this->littleEndianWord($this->bytes, $off + 6 * 4);
                    $numbAttrs = $this->littleEndianWord($this->bytes, $off + 7 * 4);
                    $off += 9 * 4;
                    $tagName = $this->compXmlString($this->bytes, $sitOff, $stOff, $nameSi);
                    $startTagLineNo = $lineNo;
                    $attr_string = "";

                    for ($ii = 0; $ii < $numbAttrs; $ii++) {
                        $attrNameNsSi = $this->littleEndianWord($this->bytes, $off);
                        $attrNameSi = $this->littleEndianWord($this->bytes, $off + 1 * 4);
                        $attrValueSi = $this->littleEndianWord($this->bytes, $off + 2 * 4);
                        $attrFlags = $this->littleEndianWord($this->bytes, $off + 3 * 4);
                        $attrResId = $this->littleEndianWord($this->bytes, $off + 4 * 4);
                        $off += 5 * 4;

                        $attrName = $this->compXmlString($this->bytes, $sitOff, $stOff, $attrNameSi);

                        if ($attrValueSi != 0xffffffff) {
                            $attrValue = $this->compXmlString($this->bytes, $sitOff, $stOff, $attrValueSi);
                        } else {
                            $attrValue = "0x" . dechex($attrResId);
                        }

                        $attr_string .= " " . $attrName . "=\"" . $attrValue . "\"";

                    }

                    $this->appendXmlIndent($indentCount, "<" . $tagName . $attr_string . ">");
                    $indentCount++;
                }
                    break;

                case self::END_TAG: {
                    $indentCount--;
                    $off += 6 * 4;
                    $tagName = $this->compXmlString($this->bytes, $sitOff, $stOff, $nameSi);
                    $this->appendXmlIndent($indentCount, "</" . $tagName . ">");
                }
                    break;

                case self::END_DOC_TAG: {
                    $this->ready = true;
                    break 2;
                }
                    break;

                case self::TEXT_TAG: {
                    // The text tag appears to be used when Android references an id value that is not
                    // a string literal
                    // To skip it, read forward until finding the sentinal 0x00000000 after finding
                    // the sentinal 0xffffffff
                    $sentinal = "0xffffffff";
                    while ($off < count($this->bytes)) {
                        $curr = "0x" . str_pad(dechex($this->littleEndianWord($this->bytes, $off)), 8, "0", STR_PAD_LEFT);

                        $off += 4;
                        if ($off > count($this->bytes)) {
                            throw new \Exception("Sentinal not found before end of file");
                        }
                        if ($curr == $sentinal && $sentinal == "0xffffffff") {
                            $sentinal = "0x00000000";
                        } else if ($curr == $sentinal) {
                            break;
                        }
                    }
                }
                    break;

                default:
                    throw new \Exception("Unrecognized tag code '" . dechex($currentTag) . "' at offset " . $off);
                    break;
            }
        }
    }

    /**
     * @param $xml
     * @param $sitOff
     * @param $stOff
     * @param $str_index
     * @return null|string
     */
    public function compXmlString($xml, $sitOff, $stOff, $str_index)
    {
        if ($str_index < 0)
            return null;

        $strOff = $stOff + $this->littleEndianWord($xml, $sitOff + $str_index * 4);
        return $this->compXmlStringAt($xml, $strOff);
    }

    /**
     * @param $indent
     * @param $str
     */
    public function appendXmlIndent($indent, $str)
    {
        $this->appendXml(substr(self::$indent_spaces, 0, min($indent * 2, strlen(self::$indent_spaces))) . $str);
    }

    /**
     * @param $str
     */
    public function appendXml($str)
    {
        $this->xml .= $str . "\r\n";
    }

    /**
     * @param $arr
     * @param $string_offset
     * @return string
     */
    public function compXmlStringAt($arr, $string_offset)
    {
        $strlen = $arr[$string_offset + 1] << 8 & 0xff00 | $arr[$string_offset] & 0xff;
        $string = "";

        for ($i = 0; $i < $strlen; $i++) {
            $string .= chr($arr[$string_offset + 2 + $i * 2]);
        }

        return $string;
    }

    /**
     * @param $arr
     * @param $off
     * @return int
     */
    public function littleEndianWord($arr, $off)
    {
        return $arr[$off + 3] << 24 & 0xff000000 | $arr[$off + 2] << 16 & 0xff0000 | $arr[$off + 1] << 8 & 0xff00 | $arr[$off] & 0xFF;
    }

    /**
     * Print XML content
     */
    public function output()
    {
        echo $this->getXmlString();
    }

    /**
     * @return mixed|string
     * @throws \Exception
     */
    public function getXmlString()
    {
        if (!$this->ready)
            $this->decompress();
        $xml = utf8_encode($this->xml);
        $xml = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $xml);
        return $xml;
    }

    /**
     * @param string $className
     * @return \SimpleXMLElement
     */
    public function getXmlObject($className = '\SimpleXmlElement')
    {
        if ($this->xmlObject === NULL || !$this->xmlObject instanceof $className)
            $this->xmlObject = simplexml_load_string($this->getXmlString(), $className);

        return $this->xmlObject;
    }
}
