<?php
namespace ApkParser;

use ApkParser\Exceptions\XmlParserException;

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
    const END_DOC_TAG = 0x0101;
    const START_TAG = 0x0102;
    const END_TAG = 0x0103;
    const TEXT_TAG = 0x0104;
    
    const RES_STRING_POOL_TYPE = 0x0001;
    const RES_XML_START_ELEMENT_TYPE = 0x0102;
    const RES_XML_RESOURCE_MAP_TYPE = 0x0180;

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
        $headerSize = $this->littleEndianShort($this->bytes, 1 * 2);
        $dataSize = $this->littleEndianWord($this->bytes, 2 * 2);
        
        $off = $headerSize;
        $resIdsOffset = -1;
        $resIdsCount = 0;
        
        
        while ($off < ($dataSize-8))
        {
            $chunkType = $this->littleEndianShort($this->bytes, $off + 0 * 2);
            $chunkHeaderSize = $this->littleEndianShort($this->bytes, $off + 1 * 2);
            $chunkSize = $this->littleEndianWord($this->bytes, $off + 2 * 2);
            if ($off + $chunkSize > $dataSize)
                break;           // not a chunk
            if ($chunkType == self::RES_STRING_POOL_TYPE)
            {
                $numbStrings = $this->littleEndianWord($this->bytes, $off + 8);
                $sitOff = $off + $chunkHeaderSize;
                $stOff = $sitOff + $numbStrings * 4;
            }
            else if ($chunkType == self::RES_XML_RESOURCE_MAP_TYPE)
            {
                  $resIdsOffset = $off + $chunkHeaderSize;
                  $resIdsCount =  ($chunkSize - $chunkHeaderSize) / 4;
            }
            else if ($chunkType == self::RES_XML_START_ELEMENT_TYPE)
            {
                  break;  // Let the next loop take care of it, though we can really move the code to this loop.
            }
            
            $off += $chunkSize;
        }
        
        $indentCount = 0;
        $startTagLineNo = -2;

        while ($off < count($this->bytes)) {
            $currentTag = $this->littleEndianShort($this->bytes, $off);
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
                        $attrNameResID = $this->littleEndianWord($this->bytes, $resIdsOffset + ($attrNameSi * 4));
                        if (empty($attrName)) {
                            $attrName = $this->getResourceNameFromID($attrNameResID);
                        }
                        

                        //-1 for 32bit PHP
                        //maybe will be better "if (dechex($attrValueSi) != 'ffffffff') {" ?
                        if (($attrValueSi != 0xffffffff) && ($attrValueSi != -1)) {
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
                    
                    $sentinal = -1;
                    while ($off < count($this->bytes)) {
                        $curr = $this->littleEndianWord($this->bytes, $off);

                        $off += 4;
                        if ($off > count($this->bytes)) {
                            throw new \Exception("Sentinal not found before end of file");
                        }
                        if ($curr == $sentinal && $sentinal == -1) {
                            $sentinal = 0;
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
        $string_offset += 2;
        $string = "";

        // We are dealing with Unicode strings, so each char is 2 bytes
        $strEnd = $string_offset + ($strlen * 2);
        if (function_exists("mb_convert_encoding"))
        {
            for ($i = $string_offset; $i < $strEnd; $i++) {
                $string .= chr($arr[$i]);
            }
            $string = mb_convert_encoding ($string , "UTF-8", "UTF-16LE");
        }
        else  // sonvert as ascii, skipping every second char
        {
            for ($i = $string_offset; $i < $strEnd; $i+=2) {
                $string .= chr($arr[$i]);
            }
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
        $signShifAmount = (PHP_INT_SIZE - 4) << 3; // the anount of bits to shift back and forth, so that we get the correct signage
        return (($arr[$off + 3] << 24 & 0xff000000 | $arr[$off + 2] << 16 & 0xff0000 | $arr[$off + 1] << 8 & 0xff00 | $arr[$off] & 0xFF) << $signShifAmount) >> $signShifAmount;
    }
    
    /**
     * @param $arr
     * @param $off
     * @return int
     */
    public function littleEndianShort($arr, $off)
    {
        $signShifAmount = (PHP_INT_SIZE - 2) << 3; // the anount of bits to shift back and forth, so that we get the correct signage
        return (($arr[$off + 1] << 8 & 0xff00 | $arr[$off] & 0xFF) << $signShifAmount) >> $signShifAmount;
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
     * @throws XmlParserException
     */
    public function getXmlObject($className = '\SimpleXmlElement')
    {
        if ($this->xmlObject === NULL || !$this->xmlObject instanceof $className) {
            $prev = libxml_use_internal_errors(true);
            $xml = $this->getXmlString();
            $this->xmlObject = simplexml_load_string($xml, $className);
            if ($this->xmlObject === false) {
                throw new XmlParserException($xml);
            }
            libxml_use_internal_errors($prev);
        }

        return $this->xmlObject;
    }
    
    public function getResourceNameFromID($id)
    {
        switch ($id)
        {
            // These values are taken from the Android Manifest class.
            case 0x1010000: $resName="theme"; break;
            case 0x1010001: $resName="label"; break;
            case 0x1010002: $resName="icon"; break;
            case 0x1010003: $resName="name"; break;
            case 0x1010004: $resName="manageSpaceActivity"; break;
            case 0x1010005: $resName="allowClearUserData"; break;
            case 0x1010006: $resName="permission"; break;
            case 0x1010007: $resName="readPermission"; break;
            case 0x1010008: $resName="writePermission"; break;
            case 0x1010009: $resName="protectionLevel"; break;
            case 0x101000a: $resName="permissionGroup"; break;
            case 0x101000b: $resName="sharedUserId"; break;
            case 0x101000c: $resName="hasCode"; break;
            case 0x101000d: $resName="persistent"; break;
            case 0x101000e: $resName="enabled"; break;
            case 0x101000f: $resName="debuggable"; break;
            case 0x1010010: $resName="exported"; break;
            case 0x1010011: $resName="process"; break;
            case 0x1010012: $resName="taskAffinity"; break;
            case 0x1010013: $resName="multiprocess"; break;
            case 0x1010014: $resName="finishOnTaskLaunch"; break;
            case 0x1010015: $resName="clearTaskOnLaunch"; break;
            case 0x1010016: $resName="stateNotNeeded"; break;
            case 0x1010017: $resName="excludeFromRecents"; break;
            case 0x1010018: $resName="authorities"; break;
            case 0x1010019: $resName="syncable"; break;
            case 0x101001a: $resName="initOrder"; break;
            case 0x101001b: $resName="grantUriPermissions"; break;
            case 0x101001c: $resName="priority"; break;
            case 0x101001d: $resName="launchMode"; break;
            case 0x101001e: $resName="screenOrientation"; break;
            case 0x101001f: $resName="configChanges"; break;
            case 0x1010020: $resName="description"; break;
            case 0x1010021: $resName="targetPackage"; break;
            case 0x1010022: $resName="handleProfiling"; break;
            case 0x1010023: $resName="functionalTest"; break;
            case 0x1010024: $resName="value"; break;
            case 0x1010025: $resName="resource"; break;
            case 0x1010026: $resName="mimeType"; break;
            case 0x1010027: $resName="scheme"; break;
            case 0x1010028: $resName="host"; break;
            case 0x1010029: $resName="port"; break;
            case 0x101002a: $resName="path"; break;
            case 0x101002b: $resName="pathPrefix"; break;
            case 0x101002c: $resName="pathPattern"; break;
            case 0x101002d: $resName="action"; break;
            case 0x101002e: $resName="data"; break;
            case 0x101002f: $resName="targetClass"; break;
            case 0x1010030: $resName="colorForeground"; break;
            case 0x1010031: $resName="colorBackground"; break;
            case 0x1010032: $resName="backgroundDimAmount"; break;
            case 0x1010033: $resName="disabledAlpha"; break;
            case 0x1010034: $resName="textAppearance"; break;
            case 0x1010035: $resName="textAppearanceInverse"; break;
            case 0x1010036: $resName="textColorPrimary"; break;
            case 0x1010037: $resName="textColorPrimaryDisableOnly"; break;
            case 0x1010038: $resName="textColorSecondary"; break;
            case 0x1010039: $resName="textColorPrimaryInverse"; break;
            case 0x101003a: $resName="textColorSecondaryInverse"; break;
            case 0x101003b: $resName="textColorPrimaryNoDisable"; break;
            case 0x101003c: $resName="textColorSecondaryNoDisable"; break;
            case 0x101003d: $resName="textColorPrimaryInverseNoDisable"; break;
            case 0x101003e: $resName="textColorSecondaryInverseNoDisable"; break;
            case 0x101003f: $resName="textColorHintInverse"; break;
            case 0x1010040: $resName="textAppearanceLarge"; break;
            case 0x1010041: $resName="textAppearanceMedium"; break;
            case 0x1010042: $resName="textAppearanceSmall"; break;
            case 0x1010043: $resName="textAppearanceLargeInverse"; break;
            case 0x1010044: $resName="textAppearanceMediumInverse"; break;
            case 0x1010045: $resName="textAppearanceSmallInverse"; break;
            case 0x1010046: $resName="textCheckMark"; break;
            case 0x1010047: $resName="textCheckMarkInverse"; break;
            case 0x1010048: $resName="buttonStyle"; break;
            case 0x1010049: $resName="buttonStyleSmall"; break;
            case 0x101004a: $resName="buttonStyleInset"; break;
            case 0x101004b: $resName="buttonStyleToggle"; break;
            case 0x101004c: $resName="galleryItemBackground"; break;
            case 0x101004d: $resName="listPreferredItemHeight"; break;
            case 0x101004e: $resName="expandableListPreferredItemPaddingLeft"; break;
            case 0x101004f: $resName="expandableListPreferredChildPaddingLeft"; break;
            case 0x1010050: $resName="expandableListPreferredItemIndicatorLeft"; break;
            case 0x1010051: $resName="expandableListPreferredItemIndicatorRight"; break;
            case 0x1010052: $resName="expandableListPreferredChildIndicatorLeft"; break;
            case 0x1010053: $resName="expandableListPreferredChildIndicatorRight"; break;
            case 0x1010054: $resName="windowBackground"; break;
            case 0x1010055: $resName="windowFrame"; break;
            case 0x1010056: $resName="windowNoTitle"; break;
            case 0x1010057: $resName="windowIsFloating"; break;
            case 0x1010058: $resName="windowIsTranslucent"; break;
            case 0x1010059: $resName="windowContentOverlay"; break;
            case 0x101005a: $resName="windowTitleSize"; break;
            case 0x101005b: $resName="windowTitleStyle"; break;
            case 0x101005c: $resName="windowTitleBackgroundStyle"; break;
            case 0x101005d: $resName="alertDialogStyle"; break;
            case 0x101005e: $resName="panelBackground"; break;
            case 0x101005f: $resName="panelFullBackground"; break;
            case 0x1010060: $resName="panelColorForeground"; break;
            case 0x1010061: $resName="panelColorBackground"; break;
            case 0x1010062: $resName="panelTextAppearance"; break;
            case 0x1010063: $resName="scrollbarSize"; break;
            case 0x1010064: $resName="scrollbarThumbHorizontal"; break;
            case 0x1010065: $resName="scrollbarThumbVertical"; break;
            case 0x1010066: $resName="scrollbarTrackHorizontal"; break;
            case 0x1010067: $resName="scrollbarTrackVertical"; break;
            case 0x1010068: $resName="scrollbarAlwaysDrawHorizontalTrack"; break;
            case 0x1010069: $resName="scrollbarAlwaysDrawVerticalTrack"; break;
            case 0x101006a: $resName="absListViewStyle"; break;
            case 0x101006b: $resName="autoCompleteTextViewStyle"; break;
            case 0x101006c: $resName="checkboxStyle"; break;
            case 0x101006d: $resName="dropDownListViewStyle"; break;
            case 0x101006e: $resName="editTextStyle"; break;
            case 0x101006f: $resName="expandableListViewStyle"; break;
            case 0x1010070: $resName="galleryStyle"; break;
            case 0x1010071: $resName="gridViewStyle"; break;
            case 0x1010072: $resName="imageButtonStyle"; break;
            case 0x1010073: $resName="imageWellStyle"; break;
            case 0x1010074: $resName="listViewStyle"; break;
            case 0x1010075: $resName="listViewWhiteStyle"; break;
            case 0x1010076: $resName="popupWindowStyle"; break;
            case 0x1010077: $resName="progressBarStyle"; break;
            case 0x1010078: $resName="progressBarStyleHorizontal"; break;
            case 0x1010079: $resName="progressBarStyleSmall"; break;
            case 0x101007a: $resName="progressBarStyleLarge"; break;
            case 0x101007b: $resName="seekBarStyle"; break;
            case 0x101007c: $resName="ratingBarStyle"; break;
            case 0x101007d: $resName="ratingBarStyleSmall"; break;
            case 0x101007e: $resName="radioButtonStyle"; break;
            case 0x101007f: $resName="scrollbarStyle"; break;
            case 0x1010080: $resName="scrollViewStyle"; break;
            case 0x1010081: $resName="spinnerStyle"; break;
            case 0x1010082: $resName="starStyle"; break;
            case 0x1010083: $resName="tabWidgetStyle"; break;
            case 0x1010084: $resName="textViewStyle"; break;
            case 0x1010085: $resName="webViewStyle"; break;
            case 0x1010086: $resName="dropDownItemStyle"; break;
            case 0x1010087: $resName="spinnerDropDownItemStyle"; break;
            case 0x1010088: $resName="dropDownHintAppearance"; break;
            case 0x1010089: $resName="spinnerItemStyle"; break;
            case 0x101008a: $resName="mapViewStyle"; break;
            case 0x101008b: $resName="preferenceScreenStyle"; break;
            case 0x101008c: $resName="preferenceCategoryStyle"; break;
            case 0x101008d: $resName="preferenceInformationStyle"; break;
            case 0x101008e: $resName="preferenceStyle"; break;
            case 0x101008f: $resName="checkBoxPreferenceStyle"; break;
            case 0x1010090: $resName="yesNoPreferenceStyle"; break;
            case 0x1010091: $resName="dialogPreferenceStyle"; break;
            case 0x1010092: $resName="editTextPreferenceStyle"; break;
            case 0x1010093: $resName="ringtonePreferenceStyle"; break;
            case 0x1010094: $resName="preferenceLayoutChild"; break;
            case 0x1010095: $resName="textSize"; break;
            case 0x1010096: $resName="typeface"; break;
            case 0x1010097: $resName="textStyle"; break;
            case 0x1010098: $resName="textColor"; break;
            case 0x1010099: $resName="textColorHighlight"; break;
            case 0x101009a: $resName="textColorHint"; break;
            case 0x101009b: $resName="textColorLink"; break;
            case 0x101009c: $resName="state_focused"; break;
            case 0x101009d: $resName="state_window_focused"; break;
            case 0x101009e: $resName="state_enabled"; break;
            case 0x101009f: $resName="state_checkable"; break;
            case 0x10100a0: $resName="state_checked"; break;
            case 0x10100a1: $resName="state_selected"; break;
            case 0x10100a2: $resName="state_active"; break;
            case 0x10100a3: $resName="state_single"; break;
            case 0x10100a4: $resName="state_first"; break;
            case 0x10100a5: $resName="state_middle"; break;
            case 0x10100a6: $resName="state_last"; break;
            case 0x10100a7: $resName="state_pressed"; break;
            case 0x10100a8: $resName="state_expanded"; break;
            case 0x10100a9: $resName="state_empty"; break;
            case 0x10100aa: $resName="state_above_anchor"; break;
            case 0x10100ab: $resName="ellipsize"; break;
            case 0x10100ac: $resName="x"; break;
            case 0x10100ad: $resName="y"; break;
            case 0x10100ae: $resName="windowAnimationStyle"; break;
            case 0x10100af: $resName="gravity"; break;
            case 0x10100b0: $resName="autoLink"; break;
            case 0x10100b1: $resName="linksClickable"; break;
            case 0x10100b2: $resName="entries"; break;
            case 0x10100b3: $resName="layout_gravity"; break;
            case 0x10100b4: $resName="windowEnterAnimation"; break;
            case 0x10100b5: $resName="windowExitAnimation"; break;
            case 0x10100b6: $resName="windowShowAnimation"; break;
            case 0x10100b7: $resName="windowHideAnimation"; break;
            case 0x10100b8: $resName="activityOpenEnterAnimation"; break;
            case 0x10100b9: $resName="activityOpenExitAnimation"; break;
            case 0x10100ba: $resName="activityCloseEnterAnimation"; break;
            case 0x10100bb: $resName="activityCloseExitAnimation"; break;
            case 0x10100bc: $resName="taskOpenEnterAnimation"; break;
            case 0x10100bd: $resName="taskOpenExitAnimation"; break;
            case 0x10100be: $resName="taskCloseEnterAnimation"; break;
            case 0x10100bf: $resName="taskCloseExitAnimation"; break;
            case 0x10100c0: $resName="taskToFrontEnterAnimation"; break;
            case 0x10100c1: $resName="taskToFrontExitAnimation"; break;
            case 0x10100c2: $resName="taskToBackEnterAnimation"; break;
            case 0x10100c3: $resName="taskToBackExitAnimation"; break;
            case 0x10100c4: $resName="orientation"; break;
            case 0x10100c5: $resName="keycode"; break;
            case 0x10100c6: $resName="fullDark"; break;
            case 0x10100c7: $resName="topDark"; break;
            case 0x10100c8: $resName="centerDark"; break;
            case 0x10100c9: $resName="bottomDark"; break;
            case 0x10100ca: $resName="fullBright"; break;
            case 0x10100cb: $resName="topBright"; break;
            case 0x10100cc: $resName="centerBright"; break;
            case 0x10100cd: $resName="bottomBright"; break;
            case 0x10100ce: $resName="bottomMedium"; break;
            case 0x10100cf: $resName="centerMedium"; break;
            case 0x10100d0: $resName="id"; break;
            case 0x10100d1: $resName="tag"; break;
            case 0x10100d2: $resName="scrollX"; break;
            case 0x10100d3: $resName="scrollY"; break;
            case 0x10100d4: $resName="background"; break;
            case 0x10100d5: $resName="padding"; break;
            case 0x10100d6: $resName="paddingLeft"; break;
            case 0x10100d7: $resName="paddingTop"; break;
            case 0x10100d8: $resName="paddingRight"; break;
            case 0x10100d9: $resName="paddingBottom"; break;
            case 0x10100da: $resName="focusable"; break;
            case 0x10100db: $resName="focusableInTouchMode"; break;
            case 0x10100dc: $resName="visibility"; break;
            case 0x10100dd: $resName="fitsSystemWindows"; break;
            case 0x10100de: $resName="scrollbars"; break;
            case 0x10100df: $resName="fadingEdge"; break;
            case 0x10100e0: $resName="fadingEdgeLength"; break;
            case 0x10100e1: $resName="nextFocusLeft"; break;
            case 0x10100e2: $resName="nextFocusRight"; break;
            case 0x10100e3: $resName="nextFocusUp"; break;
            case 0x10100e4: $resName="nextFocusDown"; break;
            case 0x10100e5: $resName="clickable"; break;
            case 0x10100e6: $resName="longClickable"; break;
            case 0x10100e7: $resName="saveEnabled"; break;
            case 0x10100e8: $resName="drawingCacheQuality"; break;
            case 0x10100e9: $resName="duplicateParentState"; break;
            case 0x10100ea: $resName="clipChildren"; break;
            case 0x10100eb: $resName="clipToPadding"; break;
            case 0x10100ec: $resName="layoutAnimation"; break;
            case 0x10100ed: $resName="animationCache"; break;
            case 0x10100ee: $resName="persistentDrawingCache"; break;
            case 0x10100ef: $resName="alwaysDrawnWithCache"; break;
            case 0x10100f0: $resName="addStatesFromChildren"; break;
            case 0x10100f1: $resName="descendantFocusability"; break;
            case 0x10100f2: $resName="layout"; break;
            case 0x10100f3: $resName="inflatedId"; break;
            case 0x10100f4: $resName="layout_width"; break;
            case 0x10100f5: $resName="layout_height"; break;
            case 0x10100f6: $resName="layout_margin"; break;
            case 0x10100f7: $resName="layout_marginLeft"; break;
            case 0x10100f8: $resName="layout_marginTop"; break;
            case 0x10100f9: $resName="layout_marginRight"; break;
            case 0x10100fa: $resName="layout_marginBottom"; break;
            case 0x10100fb: $resName="listSelector"; break;
            case 0x10100fc: $resName="drawSelectorOnTop"; break;
            case 0x10100fd: $resName="stackFromBottom"; break;
            case 0x10100fe: $resName="scrollingCache"; break;
            case 0x10100ff: $resName="textFilterEnabled"; break;
            case 0x1010100: $resName="transcriptMode"; break;
            case 0x1010101: $resName="cacheColorHint"; break;
            case 0x1010102: $resName="dial"; break;
            case 0x1010103: $resName="hand_hour"; break;
            case 0x1010104: $resName="hand_minute"; break;
            case 0x1010105: $resName="format"; break;
            case 0x1010106: $resName="checked"; break;
            case 0x1010107: $resName="button"; break;
            case 0x1010108: $resName="checkMark"; break;
            case 0x1010109: $resName="foreground"; break;
            case 0x101010a: $resName="measureAllChildren"; break;
            case 0x101010b: $resName="groupIndicator"; break;
            case 0x101010c: $resName="childIndicator"; break;
            case 0x101010d: $resName="indicatorLeft"; break;
            case 0x101010e: $resName="indicatorRight"; break;
            case 0x101010f: $resName="childIndicatorLeft"; break;
            case 0x1010110: $resName="childIndicatorRight"; break;
            case 0x1010111: $resName="childDivider"; break;
            case 0x1010112: $resName="animationDuration"; break;
            case 0x1010113: $resName="spacing"; break;
            case 0x1010114: $resName="horizontalSpacing"; break;
            case 0x1010115: $resName="verticalSpacing"; break;
            case 0x1010116: $resName="stretchMode"; break;
            case 0x1010117: $resName="columnWidth"; break;
            case 0x1010118: $resName="numColumns"; break;
            case 0x1010119: $resName="src"; break;
            case 0x101011a: $resName="antialias"; break;
            case 0x101011b: $resName="filter"; break;
            case 0x101011c: $resName="dither"; break;
            case 0x101011d: $resName="scaleType"; break;
            case 0x101011e: $resName="adjustViewBounds"; break;
            case 0x101011f: $resName="maxWidth"; break;
            case 0x1010120: $resName="maxHeight"; break;
            case 0x1010121: $resName="tint"; break;
            case 0x1010122: $resName="baselineAlignBottom"; break;
            case 0x1010123: $resName="cropToPadding"; break;
            case 0x1010124: $resName="textOn"; break;
            case 0x1010125: $resName="textOff"; break;
            case 0x1010126: $resName="baselineAligned"; break;
            case 0x1010127: $resName="baselineAlignedChildIndex"; break;
            case 0x1010128: $resName="weightSum"; break;
            case 0x1010129: $resName="divider"; break;
            case 0x101012a: $resName="dividerHeight"; break;
            case 0x101012b: $resName="choiceMode"; break;
            case 0x101012c: $resName="itemTextAppearance"; break;
            case 0x101012d: $resName="horizontalDivider"; break;
            case 0x101012e: $resName="verticalDivider"; break;
            case 0x101012f: $resName="headerBackground"; break;
            case 0x1010130: $resName="itemBackground"; break;
            case 0x1010131: $resName="itemIconDisabledAlpha"; break;
            case 0x1010132: $resName="rowHeight"; break;
            case 0x1010133: $resName="maxRows"; break;
            case 0x1010134: $resName="maxItemsPerRow"; break;
            case 0x1010135: $resName="moreIcon"; break;
            case 0x1010136: $resName="max"; break;
            case 0x1010137: $resName="progress"; break;
            case 0x1010138: $resName="secondaryProgress"; break;
            case 0x1010139: $resName="indeterminate"; break;
            case 0x101013a: $resName="indeterminateOnly"; break;
            case 0x101013b: $resName="indeterminateDrawable"; break;
            case 0x101013c: $resName="progressDrawable"; break;
            case 0x101013d: $resName="indeterminateDuration"; break;
            case 0x101013e: $resName="indeterminateBehavior"; break;
            case 0x101013f: $resName="minWidth"; break;
            case 0x1010140: $resName="minHeight"; break;
            case 0x1010141: $resName="interpolator"; break;
            case 0x1010142: $resName="thumb"; break;
            case 0x1010143: $resName="thumbOffset"; break;
            case 0x1010144: $resName="numStars"; break;
            case 0x1010145: $resName="rating"; break;
            case 0x1010146: $resName="stepSize"; break;
            case 0x1010147: $resName="isIndicator"; break;
            case 0x1010148: $resName="checkedButton"; break;
            case 0x1010149: $resName="stretchColumns"; break;
            case 0x101014a: $resName="shrinkColumns"; break;
            case 0x101014b: $resName="collapseColumns"; break;
            case 0x101014c: $resName="layout_column"; break;
            case 0x101014d: $resName="layout_span"; break;
            case 0x101014e: $resName="bufferType"; break;
            case 0x101014f: $resName="text"; break;
            case 0x1010150: $resName="hint"; break;
            case 0x1010151: $resName="textScaleX"; break;
            case 0x1010152: $resName="cursorVisible"; break;
            case 0x1010153: $resName="maxLines"; break;
            case 0x1010154: $resName="lines"; break;
            case 0x1010155: $resName="height"; break;
            case 0x1010156: $resName="minLines"; break;
            case 0x1010157: $resName="maxEms"; break;
            case 0x1010158: $resName="ems"; break;
            case 0x1010159: $resName="width"; break;
            case 0x101015a: $resName="minEms"; break;
            case 0x101015b: $resName="scrollHorizontally"; break;
            case 0x101015c: $resName="field public static final deprecated int password"; break;
            case 0x101015d: $resName="field public static final deprecated int singleLine"; break;
            case 0x101015e: $resName="selectAllOnFocus"; break;
            case 0x101015f: $resName="includeFontPadding"; break;
            case 0x1010160: $resName="maxLength"; break;
            case 0x1010161: $resName="shadowColor"; break;
            case 0x1010162: $resName="shadowDx"; break;
            case 0x1010163: $resName="shadowDy"; break;
            case 0x1010164: $resName="shadowRadius"; break;
            case 0x1010165: $resName="field public static final deprecated int numeric"; break;
            case 0x1010166: $resName="digits"; break;
            case 0x1010167: $resName="field public static final deprecated int phoneNumber"; break;
            case 0x1010168: $resName="field public static final deprecated int inputMethod"; break;
            case 0x1010169: $resName="field public static final deprecated int capitalize"; break;
            case 0x101016a: $resName="field public static final deprecated int autoText"; break;
            case 0x101016b: $resName="field public static final deprecated int editable"; break;
            case 0x101016c: $resName="freezesText"; break;
            case 0x101016d: $resName="drawableTop"; break;
            case 0x101016e: $resName="drawableBottom"; break;
            case 0x101016f: $resName="drawableLeft"; break;
            case 0x1010170: $resName="drawableRight"; break;
            case 0x1010171: $resName="drawablePadding"; break;
            case 0x1010172: $resName="completionHint"; break;
            case 0x1010173: $resName="completionHintView"; break;
            case 0x1010174: $resName="completionThreshold"; break;
            case 0x1010175: $resName="dropDownSelector"; break;
            case 0x1010176: $resName="popupBackground"; break;
            case 0x1010177: $resName="inAnimation"; break;
            case 0x1010178: $resName="outAnimation"; break;
            case 0x1010179: $resName="flipInterval"; break;
            case 0x101017a: $resName="fillViewport"; break;
            case 0x101017b: $resName="prompt"; break;
            case 0x101017c: $resName="field public static final deprecated int startYear"; break;
            case 0x101017d: $resName="field public static final deprecated int endYear"; break;
            case 0x101017e: $resName="mode"; break;
            case 0x101017f: $resName="layout_x"; break;
            case 0x1010180: $resName="layout_y"; break;
            case 0x1010181: $resName="layout_weight"; break;
            case 0x1010182: $resName="layout_toLeftOf"; break;
            case 0x1010183: $resName="layout_toRightOf"; break;
            case 0x1010184: $resName="layout_above"; break;
            case 0x1010185: $resName="layout_below"; break;
            case 0x1010186: $resName="layout_alignBaseline"; break;
            case 0x1010187: $resName="layout_alignLeft"; break;
            case 0x1010188: $resName="layout_alignTop"; break;
            case 0x1010189: $resName="layout_alignRight"; break;
            case 0x101018a: $resName="layout_alignBottom"; break;
            case 0x101018b: $resName="layout_alignParentLeft"; break;
            case 0x101018c: $resName="layout_alignParentTop"; break;
            case 0x101018d: $resName="layout_alignParentRight"; break;
            case 0x101018e: $resName="layout_alignParentBottom"; break;
            case 0x101018f: $resName="layout_centerInParent"; break;
            case 0x1010190: $resName="layout_centerHorizontal"; break;
            case 0x1010191: $resName="layout_centerVertical"; break;
            case 0x1010192: $resName="layout_alignWithParentIfMissing"; break;
            case 0x1010193: $resName="layout_scale"; break;
            case 0x1010194: $resName="visible"; break;
            case 0x1010195: $resName="variablePadding"; break;
            case 0x1010196: $resName="constantSize"; break;
            case 0x1010197: $resName="oneshot"; break;
            case 0x1010198: $resName="duration"; break;
            case 0x1010199: $resName="drawable"; break;
            case 0x101019a: $resName="shape"; break;
            case 0x101019b: $resName="innerRadiusRatio"; break;
            case 0x101019c: $resName="thicknessRatio"; break;
            case 0x101019d: $resName="startColor"; break;
            case 0x101019e: $resName="endColor"; break;
            case 0x101019f: $resName="useLevel"; break;
            case 0x10101a0: $resName="angle"; break;
            case 0x10101a1: $resName="type"; break;
            case 0x10101a2: $resName="centerX"; break;
            case 0x10101a3: $resName="centerY"; break;
            case 0x10101a4: $resName="gradientRadius"; break;
            case 0x10101a5: $resName="color"; break;
            case 0x10101a6: $resName="dashWidth"; break;
            case 0x10101a7: $resName="dashGap"; break;
            case 0x10101a8: $resName="radius"; break;
            case 0x10101a9: $resName="topLeftRadius"; break;
            case 0x10101aa: $resName="topRightRadius"; break;
            case 0x10101ab: $resName="bottomLeftRadius"; break;
            case 0x10101ac: $resName="bottomRightRadius"; break;
            case 0x10101ad: $resName="left"; break;
            case 0x10101ae: $resName="top"; break;
            case 0x10101af: $resName="right"; break;
            case 0x10101b0: $resName="bottom"; break;
            case 0x10101b1: $resName="minLevel"; break;
            case 0x10101b2: $resName="maxLevel"; break;
            case 0x10101b3: $resName="fromDegrees"; break;
            case 0x10101b4: $resName="toDegrees"; break;
            case 0x10101b5: $resName="pivotX"; break;
            case 0x10101b6: $resName="pivotY"; break;
            case 0x10101b7: $resName="insetLeft"; break;
            case 0x10101b8: $resName="insetRight"; break;
            case 0x10101b9: $resName="insetTop"; break;
            case 0x10101ba: $resName="insetBottom"; break;
            case 0x10101bb: $resName="shareInterpolator"; break;
            case 0x10101bc: $resName="fillBefore"; break;
            case 0x10101bd: $resName="fillAfter"; break;
            case 0x10101be: $resName="startOffset"; break;
            case 0x10101bf: $resName="repeatCount"; break;
            case 0x10101c0: $resName="repeatMode"; break;
            case 0x10101c1: $resName="zAdjustment"; break;
            case 0x10101c2: $resName="fromXScale"; break;
            case 0x10101c3: $resName="toXScale"; break;
            case 0x10101c4: $resName="fromYScale"; break;
            case 0x10101c5: $resName="toYScale"; break;
            case 0x10101c6: $resName="fromXDelta"; break;
            case 0x10101c7: $resName="toXDelta"; break;
            case 0x10101c8: $resName="fromYDelta"; break;
            case 0x10101c9: $resName="toYDelta"; break;
            case 0x10101ca: $resName="fromAlpha"; break;
            case 0x10101cb: $resName="toAlpha"; break;
            case 0x10101cc: $resName="delay"; break;
            case 0x10101cd: $resName="animation"; break;
            case 0x10101ce: $resName="animationOrder"; break;
            case 0x10101cf: $resName="columnDelay"; break;
            case 0x10101d0: $resName="rowDelay"; break;
            case 0x10101d1: $resName="direction"; break;
            case 0x10101d2: $resName="directionPriority"; break;
            case 0x10101d3: $resName="factor"; break;
            case 0x10101d4: $resName="cycles"; break;
            case 0x10101d5: $resName="searchMode"; break;
            case 0x10101d6: $resName="searchSuggestAuthority"; break;
            case 0x10101d7: $resName="searchSuggestPath"; break;
            case 0x10101d8: $resName="searchSuggestSelection"; break;
            case 0x10101d9: $resName="searchSuggestIntentAction"; break;
            case 0x10101da: $resName="searchSuggestIntentData"; break;
            case 0x10101db: $resName="queryActionMsg"; break;
            case 0x10101dc: $resName="suggestActionMsg"; break;
            case 0x10101dd: $resName="suggestActionMsgColumn"; break;
            case 0x10101de: $resName="menuCategory"; break;
            case 0x10101df: $resName="orderInCategory"; break;
            case 0x10101e0: $resName="checkableBehavior"; break;
            case 0x10101e1: $resName="title"; break;
            case 0x10101e2: $resName="titleCondensed"; break;
            case 0x10101e3: $resName="alphabeticShortcut"; break;
            case 0x10101e4: $resName="numericShortcut"; break;
            case 0x10101e5: $resName="checkable"; break;
            case 0x10101e6: $resName="selectable"; break;
            case 0x10101e7: $resName="orderingFromXml"; break;
            case 0x10101e8: $resName="key"; break;
            case 0x10101e9: $resName="summary"; break;
            case 0x10101ea: $resName="order"; break;
            case 0x10101eb: $resName="widgetLayout"; break;
            case 0x10101ec: $resName="dependency"; break;
            case 0x10101ed: $resName="defaultValue"; break;
            case 0x10101ee: $resName="shouldDisableView"; break;
            case 0x10101ef: $resName="summaryOn"; break;
            case 0x10101f0: $resName="summaryOff"; break;
            case 0x10101f1: $resName="disableDependentsState"; break;
            case 0x10101f2: $resName="dialogTitle"; break;
            case 0x10101f3: $resName="dialogMessage"; break;
            case 0x10101f4: $resName="dialogIcon"; break;
            case 0x10101f5: $resName="positiveButtonText"; break;
            case 0x10101f6: $resName="negativeButtonText"; break;
            case 0x10101f7: $resName="dialogLayout"; break;
            case 0x10101f8: $resName="entryValues"; break;
            case 0x10101f9: $resName="ringtoneType"; break;
            case 0x10101fa: $resName="showDefault"; break;
            case 0x10101fb: $resName="showSilent"; break;
            case 0x10101fc: $resName="scaleWidth"; break;
            case 0x10101fd: $resName="scaleHeight"; break;
            case 0x10101fe: $resName="scaleGravity"; break;
            case 0x10101ff: $resName="ignoreGravity"; break;
            case 0x1010200: $resName="foregroundGravity"; break;
            case 0x1010201: $resName="tileMode"; break;
            case 0x1010202: $resName="targetActivity"; break;
            case 0x1010203: $resName="alwaysRetainTaskState"; break;
            case 0x1010204: $resName="allowTaskReparenting"; break;
            case 0x1010205: $resName="field public static final deprecated int searchButtonText"; break;
            case 0x1010206: $resName="colorForegroundInverse"; break;
            case 0x1010207: $resName="textAppearanceButton"; break;
            case 0x1010208: $resName="listSeparatorTextViewStyle"; break;
            case 0x1010209: $resName="streamType"; break;
            case 0x101020a: $resName="clipOrientation"; break;
            case 0x101020b: $resName="centerColor"; break;
            case 0x101020c: $resName="minSdkVersion"; break;
            case 0x101020d: $resName="windowFullscreen"; break;
            case 0x101020e: $resName="unselectedAlpha"; break;
            case 0x101020f: $resName="progressBarStyleSmallTitle"; break;
            case 0x1010210: $resName="ratingBarStyleIndicator"; break;
            case 0x1010211: $resName="apiKey"; break;
            case 0x1010212: $resName="textColorTertiary"; break;
            case 0x1010213: $resName="textColorTertiaryInverse"; break;
            case 0x1010214: $resName="listDivider"; break;
            case 0x1010215: $resName="soundEffectsEnabled"; break;
            case 0x1010216: $resName="keepScreenOn"; break;
            case 0x1010217: $resName="lineSpacingExtra"; break;
            case 0x1010218: $resName="lineSpacingMultiplier"; break;
            case 0x1010219: $resName="listChoiceIndicatorSingle"; break;
            case 0x101021a: $resName="listChoiceIndicatorMultiple"; break;
            case 0x101021b: $resName="versionCode"; break;
            case 0x101021c: $resName="versionName"; break;
            case 0x101021d: $resName="marqueeRepeatLimit"; break;
            case 0x101021e: $resName="windowNoDisplay"; break;
            case 0x101021f: $resName="backgroundDimEnabled"; break;
            case 0x1010220: $resName="inputType"; break;
            case 0x1010221: $resName="isDefault"; break;
            case 0x1010222: $resName="windowDisablePreview"; break;
            case 0x1010223: $resName="privateImeOptions"; break;
            case 0x1010224: $resName="editorExtras"; break;
            case 0x1010225: $resName="settingsActivity"; break;
            case 0x1010226: $resName="fastScrollEnabled"; break;
            case 0x1010227: $resName="reqTouchScreen"; break;
            case 0x1010228: $resName="reqKeyboardType"; break;
            case 0x1010229: $resName="reqHardKeyboard"; break;
            case 0x101022a: $resName="reqNavigation"; break;
            case 0x101022b: $resName="windowSoftInputMode"; break;
            case 0x101022c: $resName="imeFullscreenBackground"; break;
            case 0x101022d: $resName="noHistory"; break;
            case 0x101022e: $resName="headerDividersEnabled"; break;
            case 0x101022f: $resName="footerDividersEnabled"; break;
            case 0x1010230: $resName="candidatesTextStyleSpans"; break;
            case 0x1010231: $resName="smoothScrollbar"; break;
            case 0x1010232: $resName="reqFiveWayNav"; break;
            case 0x1010233: $resName="keyBackground"; break;
            case 0x1010234: $resName="keyTextSize"; break;
            case 0x1010235: $resName="labelTextSize"; break;
            case 0x1010236: $resName="keyTextColor"; break;
            case 0x1010237: $resName="keyPreviewLayout"; break;
            case 0x1010238: $resName="keyPreviewOffset"; break;
            case 0x1010239: $resName="keyPreviewHeight"; break;
            case 0x101023a: $resName="verticalCorrection"; break;
            case 0x101023b: $resName="popupLayout"; break;
            case 0x101023c: $resName="state_long_pressable"; break;
            case 0x101023d: $resName="keyWidth"; break;
            case 0x101023e: $resName="keyHeight"; break;
            case 0x101023f: $resName="horizontalGap"; break;
            case 0x1010240: $resName="verticalGap"; break;
            case 0x1010241: $resName="rowEdgeFlags"; break;
            case 0x1010242: $resName="codes"; break;
            case 0x1010243: $resName="popupKeyboard"; break;
            case 0x1010244: $resName="popupCharacters"; break;
            case 0x1010245: $resName="keyEdgeFlags"; break;
            case 0x1010246: $resName="isModifier"; break;
            case 0x1010247: $resName="isSticky"; break;
            case 0x1010248: $resName="isRepeatable"; break;
            case 0x1010249: $resName="iconPreview"; break;
            case 0x101024a: $resName="keyOutputText"; break;
            case 0x101024b: $resName="keyLabel"; break;
            case 0x101024c: $resName="keyIcon"; break;
            case 0x101024d: $resName="keyboardMode"; break;
            case 0x101024e: $resName="isScrollContainer"; break;
            case 0x101024f: $resName="fillEnabled"; break;
            case 0x1010250: $resName="updatePeriodMillis"; break;
            case 0x1010251: $resName="initialLayout"; break;
            case 0x1010252: $resName="voiceSearchMode"; break;
            case 0x1010253: $resName="voiceLanguageModel"; break;
            case 0x1010254: $resName="voicePromptText"; break;
            case 0x1010255: $resName="voiceLanguage"; break;
            case 0x1010256: $resName="voiceMaxResults"; break;
            case 0x1010257: $resName="bottomOffset"; break;
            case 0x1010258: $resName="topOffset"; break;
            case 0x1010259: $resName="allowSingleTap"; break;
            case 0x101025a: $resName="handle"; break;
            case 0x101025b: $resName="content"; break;
            case 0x101025c: $resName="animateOnClick"; break;
            case 0x101025d: $resName="configure"; break;
            case 0x101025e: $resName="hapticFeedbackEnabled"; break;
            case 0x101025f: $resName="innerRadius"; break;
            case 0x1010260: $resName="thickness"; break;
            case 0x1010261: $resName="sharedUserLabel"; break;
            case 0x1010262: $resName="dropDownWidth"; break;
            case 0x1010263: $resName="dropDownAnchor"; break;
            case 0x1010264: $resName="imeOptions"; break;
            case 0x1010265: $resName="imeActionLabel"; break;
            case 0x1010266: $resName="imeActionId"; break;
            case 0x1010268: $resName="imeExtractEnterAnimation"; break;
            case 0x1010269: $resName="imeExtractExitAnimation"; break;
            case 0x101026a: $resName="tension"; break;
            case 0x101026b: $resName="extraTension"; break;
            case 0x101026c: $resName="anyDensity"; break;
            case 0x101026d: $resName="searchSuggestThreshold"; break;
            case 0x101026e: $resName="includeInGlobalSearch"; break;
            case 0x101026f: $resName="onClick"; break;
            case 0x1010270: $resName="targetSdkVersion"; break;
            case 0x1010271: $resName="maxSdkVersion"; break;
            case 0x1010272: $resName="testOnly"; break;
            case 0x1010273: $resName="contentDescription"; break;
            case 0x1010274: $resName="gestureStrokeWidth"; break;
            case 0x1010275: $resName="gestureColor"; break;
            case 0x1010276: $resName="uncertainGestureColor"; break;
            case 0x1010277: $resName="fadeOffset"; break;
            case 0x1010278: $resName="fadeDuration"; break;
            case 0x1010279: $resName="gestureStrokeType"; break;
            case 0x101027a: $resName="gestureStrokeLengthThreshold"; break;
            case 0x101027b: $resName="gestureStrokeSquarenessThreshold"; break;
            case 0x101027c: $resName="gestureStrokeAngleThreshold"; break;
            case 0x101027d: $resName="eventsInterceptionEnabled"; break;
            case 0x101027e: $resName="fadeEnabled"; break;
            case 0x101027f: $resName="backupAgent"; break;
            case 0x1010280: $resName="allowBackup"; break;
            case 0x1010281: $resName="glEsVersion"; break;
            case 0x1010282: $resName="queryAfterZeroResults"; break;
            case 0x1010283: $resName="dropDownHeight"; break;
            case 0x1010284: $resName="smallScreens"; break;
            case 0x1010285: $resName="normalScreens"; break;
            case 0x1010286: $resName="largeScreens"; break;
            case 0x1010287: $resName="progressBarStyleInverse"; break;
            case 0x1010288: $resName="progressBarStyleSmallInverse"; break;
            case 0x1010289: $resName="progressBarStyleLargeInverse"; break;
            case 0x101028a: $resName="searchSettingsDescription"; break;
            case 0x101028b: $resName="textColorPrimaryInverseDisableOnly"; break;
            case 0x101028c: $resName="autoUrlDetect"; break;
            case 0x101028d: $resName="resizeable"; break;
            case 0x101028e: $resName="required"; break;
            case 0x101028f: $resName="accountType"; break;
            case 0x1010290: $resName="contentAuthority"; break;
            case 0x1010291: $resName="userVisible"; break;
            case 0x1010292: $resName="windowShowWallpaper"; break;
            case 0x1010293: $resName="wallpaperOpenEnterAnimation"; break;
            case 0x1010294: $resName="wallpaperOpenExitAnimation"; break;
            case 0x1010295: $resName="wallpaperCloseEnterAnimation"; break;
            case 0x1010296: $resName="wallpaperCloseExitAnimation"; break;
            case 0x1010297: $resName="wallpaperIntraOpenEnterAnimation"; break;
            case 0x1010298: $resName="wallpaperIntraOpenExitAnimation"; break;
            case 0x1010299: $resName="wallpaperIntraCloseEnterAnimation"; break;
            case 0x101029a: $resName="wallpaperIntraCloseExitAnimation"; break;
            case 0x101029b: $resName="supportsUploading"; break;
            case 0x101029c: $resName="killAfterRestore"; break;
            case 0x101029d: $resName="field public static final deprecated int restoreNeedsApplication"; break;
            case 0x101029e: $resName="smallIcon"; break;
            case 0x101029f: $resName="accountPreferences"; break;
            case 0x10102a0: $resName="textAppearanceSearchResultSubtitle"; break;
            case 0x10102a1: $resName="textAppearanceSearchResultTitle"; break;
            case 0x10102a2: $resName="summaryColumn"; break;
            case 0x10102a3: $resName="detailColumn"; break;
            case 0x10102a4: $resName="detailSocialSummary"; break;
            case 0x10102a5: $resName="thumbnail"; break;
            case 0x10102a6: $resName="detachWallpaper"; break;
            case 0x10102a7: $resName="finishOnCloseSystemDialogs"; break;
            case 0x10102a8: $resName="scrollbarFadeDuration"; break;
            case 0x10102a9: $resName="scrollbarDefaultDelayBeforeFade"; break;
            case 0x10102aa: $resName="fadeScrollbars"; break;
            case 0x10102ab: $resName="colorBackgroundCacheHint"; break;
            case 0x10102ac: $resName="dropDownHorizontalOffset"; break;
            case 0x10102ad: $resName="dropDownVerticalOffset"; break;
            case 0x10102ae: $resName="quickContactBadgeStyleWindowSmall"; break;
            case 0x10102af: $resName="quickContactBadgeStyleWindowMedium"; break;
            case 0x10102b0: $resName="quickContactBadgeStyleWindowLarge"; break;
            case 0x10102b1: $resName="quickContactBadgeStyleSmallWindowSmall"; break;
            case 0x10102b2: $resName="quickContactBadgeStyleSmallWindowMedium"; break;
            case 0x10102b3: $resName="quickContactBadgeStyleSmallWindowLarge"; break;
            case 0x10102b4: $resName="author"; break;
            case 0x10102b5: $resName="autoStart"; break;
            case 0x10102b6: $resName="expandableListViewWhiteStyle"; break;
            case 0x10102b7: $resName="installLocation"; break;
            case 0x10102b8: $resName="vmSafeMode"; break;
            case 0x10102b9: $resName="webTextViewStyle"; break;
            case 0x10102ba: $resName="restoreAnyVersion"; break;
            case 0x10102bb: $resName="tabStripLeft"; break;
            case 0x10102bc: $resName="tabStripRight"; break;
            case 0x10102bd: $resName="tabStripEnabled"; break;
            case 0x10102be: $resName="logo"; break;
            case 0x10102bf: $resName="xlargeScreens"; break;
            case 0x10102c0: $resName="immersive"; break;
            case 0x10102c1: $resName="overScrollMode"; break;
            case 0x10102c2: $resName="overScrollHeader"; break;
            case 0x10102c3: $resName="overScrollFooter"; break;
            case 0x10102c4: $resName="filterTouchesWhenObscured"; break;
            case 0x10102c5: $resName="textSelectHandleLeft"; break;
            case 0x10102c6: $resName="textSelectHandleRight"; break;
            case 0x10102c7: $resName="textSelectHandle"; break;
            case 0x10102c8: $resName="textSelectHandleWindowStyle"; break;
            case 0x10102c9: $resName="popupAnimationStyle"; break;
            case 0x10102ca: $resName="screenSize"; break;
            case 0x10102cb: $resName="screenDensity"; break;
            case 0x10102cc: $resName="allContactsName"; break;
            case 0x10102cd: $resName="windowActionBar"; break;
            case 0x10102ce: $resName="actionBarStyle"; break;
            case 0x10102cf: $resName="navigationMode"; break;
            case 0x10102d0: $resName="displayOptions"; break;
            case 0x10102d1: $resName="subtitle"; break;
            case 0x10102d2: $resName="customNavigationLayout"; break;
            case 0x10102d3: $resName="hardwareAccelerated"; break;
            case 0x10102d4: $resName="measureWithLargestChild"; break;
            case 0x10102d5: $resName="animateFirstView"; break;
            case 0x10102d6: $resName="dropDownSpinnerStyle"; break;
            case 0x10102d7: $resName="actionDropDownStyle"; break;
            case 0x10102d8: $resName="actionButtonStyle"; break;
            case 0x10102d9: $resName="showAsAction"; break;
            case 0x10102da: $resName="previewImage"; break;
            case 0x10102db: $resName="actionModeBackground"; break;
            case 0x10102dc: $resName="actionModeCloseDrawable"; break;
            case 0x10102dd: $resName="windowActionModeOverlay"; break;
            case 0x10102de: $resName="valueFrom"; break;
            case 0x10102df: $resName="valueTo"; break;
            case 0x10102e0: $resName="valueType"; break;
            case 0x10102e1: $resName="propertyName"; break;
            case 0x10102e2: $resName="ordering"; break;
            case 0x10102e3: $resName="fragment"; break;
            case 0x10102e4: $resName="windowActionBarOverlay"; break;
            case 0x10102e5: $resName="fragmentOpenEnterAnimation"; break;
            case 0x10102e6: $resName="fragmentOpenExitAnimation"; break;
            case 0x10102e7: $resName="fragmentCloseEnterAnimation"; break;
            case 0x10102e8: $resName="fragmentCloseExitAnimation"; break;
            case 0x10102e9: $resName="fragmentFadeEnterAnimation"; break;
            case 0x10102ea: $resName="fragmentFadeExitAnimation"; break;
            case 0x10102eb: $resName="actionBarSize"; break;
            case 0x10102ec: $resName="imeSubtypeLocale"; break;
            case 0x10102ed: $resName="imeSubtypeMode"; break;
            case 0x10102ee: $resName="imeSubtypeExtraValue"; break;
            case 0x10102ef: $resName="splitMotionEvents"; break;
            case 0x10102f0: $resName="listChoiceBackgroundIndicator"; break;
            case 0x10102f1: $resName="spinnerMode"; break;
            case 0x10102f2: $resName="animateLayoutChanges"; break;
            case 0x10102f3: $resName="actionBarTabStyle"; break;
            case 0x10102f4: $resName="actionBarTabBarStyle"; break;
            case 0x10102f5: $resName="actionBarTabTextStyle"; break;
            case 0x10102f6: $resName="actionOverflowButtonStyle"; break;
            case 0x10102f7: $resName="actionModeCloseButtonStyle"; break;
            case 0x10102f8: $resName="titleTextStyle"; break;
            case 0x10102f9: $resName="subtitleTextStyle"; break;
            case 0x10102fa: $resName="iconifiedByDefault"; break;
            case 0x10102fb: $resName="actionLayout"; break;
            case 0x10102fc: $resName="actionViewClass"; break;
            case 0x10102fd: $resName="activatedBackgroundIndicator"; break;
            case 0x10102fe: $resName="state_activated"; break;
            case 0x10102ff: $resName="listPopupWindowStyle"; break;
            case 0x1010300: $resName="popupMenuStyle"; break;
            case 0x1010301: $resName="textAppearanceLargePopupMenu"; break;
            case 0x1010302: $resName="textAppearanceSmallPopupMenu"; break;
            case 0x1010303: $resName="breadCrumbTitle"; break;
            case 0x1010304: $resName="breadCrumbShortTitle"; break;
            case 0x1010305: $resName="listDividerAlertDialog"; break;
            case 0x1010306: $resName="textColorAlertDialogListItem"; break;
            case 0x1010307: $resName="loopViews"; break;
            case 0x1010308: $resName="dialogTheme"; break;
            case 0x1010309: $resName="alertDialogTheme"; break;
            case 0x101030a: $resName="dividerVertical"; break;
            case 0x101030b: $resName="homeAsUpIndicator"; break;
            case 0x101030c: $resName="enterFadeDuration"; break;
            case 0x101030d: $resName="exitFadeDuration"; break;
            case 0x101030e: $resName="selectableItemBackground"; break;
            case 0x101030f: $resName="autoAdvanceViewId"; break;
            case 0x1010310: $resName="useIntrinsicSizeAsMinimum"; break;
            case 0x1010311: $resName="actionModeCutDrawable"; break;
            case 0x1010312: $resName="actionModeCopyDrawable"; break;
            case 0x1010313: $resName="actionModePasteDrawable"; break;
            case 0x1010314: $resName="textEditPasteWindowLayout"; break;
            case 0x1010315: $resName="textEditNoPasteWindowLayout"; break;
            case 0x1010316: $resName="textIsSelectable"; break;
            case 0x1010317: $resName="windowEnableSplitTouch"; break;
            case 0x1010318: $resName="indeterminateProgressStyle"; break;
            case 0x1010319: $resName="progressBarPadding"; break;
            case 0x101031a: $resName="field public static final deprecated int animationResolution"; break;
            case 0x101031b: $resName="state_accelerated"; break;
            case 0x101031c: $resName="baseline"; break;
            case 0x101031d: $resName="homeLayout"; break;
            case 0x101031e: $resName="opacity"; break;
            case 0x101031f: $resName="alpha"; break;
            case 0x1010320: $resName="transformPivotX"; break;
            case 0x1010321: $resName="transformPivotY"; break;
            case 0x1010322: $resName="translationX"; break;
            case 0x1010323: $resName="translationY"; break;
            case 0x1010324: $resName="scaleX"; break;
            case 0x1010325: $resName="scaleY"; break;
            case 0x1010326: $resName="rotation"; break;
            case 0x1010327: $resName="rotationX"; break;
            case 0x1010328: $resName="rotationY"; break;
            case 0x1010329: $resName="showDividers"; break;
            case 0x101032a: $resName="dividerPadding"; break;
            case 0x101032b: $resName="borderlessButtonStyle"; break;
            case 0x101032c: $resName="dividerHorizontal"; break;
            case 0x101032d: $resName="itemPadding"; break;
            case 0x101032e: $resName="buttonBarStyle"; break;
            case 0x101032f: $resName="buttonBarButtonStyle"; break;
            case 0x1010330: $resName="segmentedButtonStyle"; break;
            case 0x1010331: $resName="staticWallpaperPreview"; break;
            case 0x1010332: $resName="allowParallelSyncs"; break;
            case 0x1010333: $resName="isAlwaysSyncable"; break;
            case 0x1010334: $resName="verticalScrollbarPosition"; break;
            case 0x1010335: $resName="fastScrollAlwaysVisible"; break;
            case 0x1010336: $resName="fastScrollThumbDrawable"; break;
            case 0x1010337: $resName="fastScrollPreviewBackgroundLeft"; break;
            case 0x1010338: $resName="fastScrollPreviewBackgroundRight"; break;
            case 0x1010339: $resName="fastScrollTrackDrawable"; break;
            case 0x101033a: $resName="fastScrollOverlayPosition"; break;
            case 0x101033b: $resName="customTokens"; break;
            case 0x101033c: $resName="nextFocusForward"; break;
            case 0x101033d: $resName="firstDayOfWeek"; break;
            case 0x101033e: $resName="showWeekNumber"; break;
            case 0x101033f: $resName="minDate"; break;
            case 0x1010340: $resName="maxDate"; break;
            case 0x1010341: $resName="shownWeekCount"; break;
            case 0x1010342: $resName="selectedWeekBackgroundColor"; break;
            case 0x1010343: $resName="focusedMonthDateColor"; break;
            case 0x1010344: $resName="unfocusedMonthDateColor"; break;
            case 0x1010345: $resName="weekNumberColor"; break;
            case 0x1010346: $resName="weekSeparatorLineColor"; break;
            case 0x1010347: $resName="selectedDateVerticalBar"; break;
            case 0x1010348: $resName="weekDayTextAppearance"; break;
            case 0x1010349: $resName="dateTextAppearance"; break;
            case 0x101034b: $resName="spinnersShown"; break;
            case 0x101034c: $resName="calendarViewShown"; break;
            case 0x101034d: $resName="state_multiline"; break;
            case 0x101034e: $resName="detailsElementBackground"; break;
            case 0x101034f: $resName="textColorHighlightInverse"; break;
            case 0x1010350: $resName="textColorLinkInverse"; break;
            case 0x1010351: $resName="editTextColor"; break;
            case 0x1010352: $resName="editTextBackground"; break;
            case 0x1010353: $resName="horizontalScrollViewStyle"; break;
            case 0x1010354: $resName="layerType"; break;
            case 0x1010355: $resName="alertDialogIcon"; break;
            case 0x1010356: $resName="windowMinWidthMajor"; break;
            case 0x1010357: $resName="windowMinWidthMinor"; break;
            case 0x1010358: $resName="queryHint"; break;
            case 0x1010359: $resName="fastScrollTextColor"; break;
            case 0x101035a: $resName="largeHeap"; break;
            case 0x101035b: $resName="windowCloseOnTouchOutside"; break;
            case 0x101035c: $resName="datePickerStyle"; break;
            case 0x101035d: $resName="calendarViewStyle"; break;
            case 0x101035e: $resName="textEditSidePasteWindowLayout"; break;
            case 0x101035f: $resName="textEditSideNoPasteWindowLayout"; break;
            case 0x1010360: $resName="actionMenuTextAppearance"; break;
            case 0x1010361: $resName="actionMenuTextColor"; break;
            case 0x1010362: $resName="textCursorDrawable"; break;
            case 0x1010363: $resName="resizeMode"; break;
            case 0x1010364: $resName="requiresSmallestWidthDp"; break;
            case 0x1010365: $resName="compatibleWidthLimitDp"; break;
            case 0x1010366: $resName="largestWidthLimitDp"; break;
            case 0x1010367: $resName="state_hovered"; break;
            case 0x1010368: $resName="state_drag_can_accept"; break;
            case 0x1010369: $resName="state_drag_hovered"; break;
            case 0x101036a: $resName="stopWithTask"; break;
            case 0x101036b: $resName="switchTextOn"; break;
            case 0x101036c: $resName="switchTextOff"; break;
            case 0x101036d: $resName="switchPreferenceStyle"; break;
            case 0x101036e: $resName="switchTextAppearance"; break;
            case 0x101036f: $resName="track"; break;
            case 0x1010370: $resName="switchMinWidth"; break;
            case 0x1010371: $resName="switchPadding"; break;
            case 0x1010372: $resName="thumbTextPadding"; break;
            case 0x1010373: $resName="textSuggestionsWindowStyle"; break;
            case 0x1010374: $resName="textEditSuggestionItemLayout"; break;
            case 0x1010375: $resName="rowCount"; break;
            case 0x1010376: $resName="rowOrderPreserved"; break;
            case 0x1010377: $resName="columnCount"; break;
            case 0x1010378: $resName="columnOrderPreserved"; break;
            case 0x1010379: $resName="useDefaultMargins"; break;
            case 0x101037a: $resName="alignmentMode"; break;
            case 0x101037b: $resName="layout_row"; break;
            case 0x101037c: $resName="layout_rowSpan"; break;
            case 0x101037d: $resName="layout_columnSpan"; break;
            case 0x101037e: $resName="actionModeSelectAllDrawable"; break;
            case 0x101037f: $resName="isAuxiliary"; break;
            case 0x1010380: $resName="accessibilityEventTypes"; break;
            case 0x1010381: $resName="packageNames"; break;
            case 0x1010382: $resName="accessibilityFeedbackType"; break;
            case 0x1010383: $resName="notificationTimeout"; break;
            case 0x1010384: $resName="accessibilityFlags"; break;
            case 0x1010385: $resName="canRetrieveWindowContent"; break;
            case 0x1010386: $resName="listPreferredItemHeightLarge"; break;
            case 0x1010387: $resName="listPreferredItemHeightSmall"; break;
            case 0x1010388: $resName="actionBarSplitStyle"; break;
            case 0x1010389: $resName="actionProviderClass"; break;
            case 0x101038a: $resName="backgroundStacked"; break;
            case 0x101038b: $resName="backgroundSplit"; break;
            case 0x101038c: $resName="textAllCaps"; break;
            case 0x101038d: $resName="colorPressedHighlight"; break;
            case 0x101038e: $resName="colorLongPressedHighlight"; break;
            case 0x101038f: $resName="colorFocusedHighlight"; break;
            case 0x1010390: $resName="colorActivatedHighlight"; break;
            case 0x1010391: $resName="colorMultiSelectHighlight"; break;
            case 0x1010392: $resName="drawableStart"; break;
            case 0x1010393: $resName="drawableEnd"; break;
            case 0x1010394: $resName="actionModeStyle"; break;
            case 0x1010395: $resName="minResizeWidth"; break;
            case 0x1010396: $resName="minResizeHeight"; break;
            case 0x1010397: $resName="actionBarWidgetTheme"; break;
            case 0x1010398: $resName="uiOptions"; break;
            case 0x1010399: $resName="subtypeLocale"; break;
            case 0x101039a: $resName="subtypeExtraValue"; break;
            case 0x101039b: $resName="actionBarDivider"; break;
            case 0x101039c: $resName="actionBarItemBackground"; break;
            case 0x101039d: $resName="actionModeSplitBackground"; break;
            case 0x101039e: $resName="textAppearanceListItem"; break;
            case 0x101039f: $resName="textAppearanceListItemSmall"; break;
            case 0x10103a0: $resName="targetDescriptions"; break;
            case 0x10103a1: $resName="directionDescriptions"; break;
            case 0x10103a2: $resName="overridesImplicitlyEnabledSubtype"; break;
            case 0x10103a3: $resName="listPreferredItemPaddingLeft"; break;
            case 0x10103a4: $resName="listPreferredItemPaddingRight"; break;
            case 0x10103a5: $resName="requiresFadingEdge"; break;
            case 0x10103a6: $resName="publicKey"; break;
            case 0x10103a7: $resName="parentActivityName"; break;
            case 0x10103a9: $resName="isolatedProcess"; break;
            case 0x10103aa: $resName="importantForAccessibility"; break;
            case 0x10103ab: $resName="keyboardLayout"; break;
            case 0x10103ac: $resName="fontFamily"; break;
            case 0x10103ad: $resName="mediaRouteButtonStyle"; break;
            case 0x10103ae: $resName="mediaRouteTypes"; break;
            case 0x10103af: $resName="supportsRtl"; break;
            case 0x10103b0: $resName="textDirection"; break;
            case 0x10103b1: $resName="textAlignment"; break;
            case 0x10103b2: $resName="layoutDirection"; break;
            case 0x10103b3: $resName="paddingStart"; break;
            case 0x10103b4: $resName="paddingEnd"; break;                                              
            case 0x10103b5: $resName="layout_marginStart"; break;
            case 0x10103b6: $resName="layout_marginEnd"; break;
            case 0x10103b7: $resName="layout_toStartOf"; break;
            case 0x10103b8: $resName="layout_toEndOf"; break;
            case 0x10103b9: $resName="layout_alignStart"; break;
            case 0x10103ba: $resName="layout_alignEnd"; break;
            case 0x10103bb: $resName="layout_alignParentStart"; break;
            case 0x10103bc: $resName="layout_alignParentEnd"; break;
            case 0x10103bd: $resName="listPreferredItemPaddingStart"; break;
            case 0x10103be: $resName="listPreferredItemPaddingEnd"; break;
            case 0x10103bf: $resName="singleUser"; break;
            case 0x10103c0: $resName="presentationTheme"; break;
            case 0x10103c1: $resName="subtypeId"; break;
            case 0x10103c2: $resName="initialKeyguardLayout"; break;
            case 0x10103c4: $resName="widgetCategory"; break;
            case 0x10103c5: $resName="permissionGroupFlags"; break;
            case 0x10103c6: $resName="labelFor"; break;
            case 0x10103c7: $resName="permissionFlags"; break;
            case 0x10103c8: $resName="checkedTextViewStyle"; break;
            case 0x10103c9: $resName="showOnLockScreen"; break;
            case 0x10103ca: $resName="format12Hour"; break;
            case 0x10103cb: $resName="format24Hour"; break;
            case 0x10103cc: $resName="timeZone"; break;
            default: $resName = "0x" . dechex($id);
        }
        return $resName;
    }
}
