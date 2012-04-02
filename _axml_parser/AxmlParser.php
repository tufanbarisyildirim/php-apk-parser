<?php
    class AXMLParser {

        /**
        * Types of returned tags.
        * Values are compatible to those in XmlPullParser.
        */
        const   START_DOCUMENT  =0;
        const   END_DOCUMENT    =1;
        const   START_TAG       =2;
        const   END_TAG         =3;
        const   TEXT            =4;


        private $m_stream;
        private $m_nextException;
        private $m_tagType;
        private $m_tagSourceLine;


        const AXML_CHUNK_TYPE         = 0x00080003;
        const RESOURCEIDS_CHUNK_TYPE  = 0x00080180;

        /**
        * Creates object and reads file info.
        * Call next() to read first tag.
        */
        public function __construct(InputStream $stream) 
        {
            $this->m_stream = $stream;
            $this->doStart();
        }

        /**
        * Closes parser:
        *      * closes (and nulls) underlying stream
        *      * nulls dynamic data
        *      * moves object to 'closed' state, where methods
        *        return invalid values and next() throws IOException.
        */
        public function close() 
        {
            $this->m_stream->close();
            $this->resetState();
        }

        /**
        * Advances to the next tag.
        * Once method returns END_DOCUMENT, it always returns END_DOCUMENT.
        * Once method throws an exception, it always throws the same exception.
        *
        */
        public function next() 
        {
            if ($this->m_nextException != null) 
            {
                throw $this->m_nextException;
            }
            try 
            {
                return $thsi->doNext();
            }
            catch (Exception $e) 
            {
                $this->m_nextException = $e;
                $this->resetState();
                throw $e;
            }
        }

        /**
        * Returns current tag type.
        */
        public function getType() {
            return $this->m_tagType;
        }

        /**
        * Returns name for the current tag.
        */
        public function getName() 
        {
            if ($this->m_tagName == -1) 
            {
                return null;
            }
            return $this->getString($this->m_tagName);
        }

        /**
        * Returns line number in the original XML where the current tag was.
        */
        public function getLineNumber() {
            return $this->m_tagSourceLine;
        }

        /**
        * Returns count of attributes for the current tag.
        */
        public function getAttributeCount() {
            if (m_tagAttributes==null) {
                return -1;
            }
            return m_tagAttributes.length;
        }

        /**
        * Returns attribute namespace.
        */
        public function  getAttributeNamespace($index) {
            return $this->getString($this->getAttribute($index)->namespace);
        }

        /**
        * Returns attribute name.
        */
        public function getAttributeName($index) 
        {
            return $this->getString($this->getAttribute($index)->name);
        }

        /**
        * Returns attribute resource ID.
        */
        public function getAttributeResourceID($index) {
            $resourceIndex = $this->getAttribute($index)->name;
            if ($this->m_resourceIDs == null ||
                $resourceIndex<0 || $resourceIndex >= count($this->m_resourceIDs))
            {
                return 0;
            }
            return $this->m_resourceIDs[$resourceIndex];
        }

        /**
        * Returns type of attribute value.
        * See TypedValue.TYPE_ values.
        */
        public function getAttributeValueType($index) 
        {
            return $this->getAttribute($index)->valueType;
        }

        /**
        * For attributes of type TypedValue.TYPE_STRING returns
        *  string value. For other types returns empty string.
        */
        public function getAttributeValueString($index) {
            return $this->getString($this->getAttribute($index)->valueString);
        }

        /**
        * Returns integer attribute value.
        * This integer interpreted according to attribute type.
        */
        public function getAttributeValue($index) 
        {
            return $this->getAttribute($index)->value;
        }

        private function resetState() 
        {
            $this->m_tagType        = -1;
            $this->m_tagSourceLine  = -1;
            $this->m_tagName        = -1;
            $this->m_tagAttributes  = null;


        }
        private function doStart() 
        {
            ReadUtil::readCheckType($this->m_stream,self::AXML_CHUNK_TYPE);
            /*chunk size*/ReadUtil::readInt($this->m_stream);
            $this->m_strings = StringBlock::read(new IntReader($this->m_stream,true));
            
            ReadUtil::readCheckType($this->m_stream,self::RESOURCEIDS_CHUNK_TYPE);
            $chunkSize = ReadUtil::readInt($this->m_stream);
            if ($chunkSize<8 || ($chunkSize%4)!=0) 
            {
                throw new IOException("Invalid resource ids size (" + $chunkSize + ").");
            }

            $this->m_resourceIDs = ReadUtil::readIntArray($this->m_stream,$chunkSize / 4-2);
            $this->resetState();
        }

        private function doNext() 
        {
            if ($this->m_tagType == self::END_DOCUMENT) 
            {
                return self::END_DOCUMENT;
            }

            $this->m_tagType = (ReadUtil::readInt($this->m_stream) & 0xFF);/*other 3 bytes?*/
            /*some source length*/ReadUtil::readInt($this->m_stream);
            $this->m_tagSourceLine = ReadUtil::readInt($this->m_stream);
            /*0xFFFFFFFF*/ReadUtil::readInt($this->m_stream);

            $this->m_tagName =- 1;
            $this->m_tagAttributes = null;

            switch ($this->m_tagType) 
            {
                case self::START_DOCUMENT:
                {
                    /*namespace?*/ReadUtil::readInt($this->m_stream);
                    /*name?*/ReadUtil::readInt($this->m_stream);
                    break;
                }
                case self::START_TAG:
                {
                    /*0xFFFFFFFF*/ReadUtil::readInt($this->m_stream);
                    $this->m_tagName=ReadUtil::readInt($this->m_stream);
                    /*flags?*/ReadUtil::readInt($this->m_stream);
                    $attributeCount = ReadUtil::readInt($this->m_stream);
                    /*?*/ReadUtil::readInt($this->m_stream);
                    $this->m_tagAttributes = array();
                    for ($i=0; $i!=$attributeCount; ++$i) 
                    {
                        $attribute=new TagAttribute();
                        $attribute->namespace = ReadUtil::readInt($this->m_stream);
                        $attribute->name = ReadUtil::readInt($this->m_stream);
                        $attribute->valueString = ReadUtil::readInt($this->m_stream);
                        $attribute->valueType = (ReadUtil::readInt($this->m_stream) >> 24);/*other 3 bytes?*/
                        $attribute->value=ReadUtil::readInt($this->m_stream);

                        $this->m_tagAttributes[$i]=$attribute;
                    }
                    break;
                }
                case self::END_TAG:
                {
                    /*0xFFFFFFFF*/ReadUtil::readInt($this->m_stream);
                    $this->m_tagName = ReadUtil::readInt($this->m_stream);
                    break;
                }
                case self::TEXT:
                {
                    $this->m_tagName=ReadUtil::readInt($this->m_stream);
                    /*?*/ReadUtil::readInt($this->m_stream);
                    /*?*/ReadUtil::readInt($this->m_stream);
                    break;
                }
                case self::END_DOCUMENT:
                {
                    /*namespace?*/ReadUtil::readInt($this->m_stream);
                    /*name?*/ReadUtil::readInt($this->m_stream);
                    break;
                }
                default:
                {
                    throw new Exception("Invalid tag type (" . $this->m_tagType . ").");
                }
            }
            return $this->m_tagType;
        }

        /**
        * @param mixed $index
        * @return TagAttribute
        */
        private function getAttribute($index) 
        {
            if ($this->m_tagAttributes==null) 
            {
                throw new Exception("Attributes are not available.");
            }
            if ($index >= count($this->m_tagAttributes)) 
            {
                throw new Exception("Invalid attribute index (" . $index . ").");
            }
            return $this->m_tagAttributes[$index];
        }

        private function  getString($index) {
            if ($index==-1) 
            {
                return "";
            }
            return $this->m_strings[$index]; //$this->m_strings.getRaw(index);
        }
}