<?php
    class IntReader extends Stream
    {

        /**
        * @var InputStream
        */
        private $m_stream;

        /**
        * @var bool
        */
        private $m_bigEndian;
        /**
        * @var int
        */
        private $m_position;


        public function __construct(InputStream $stream,$bigEndian) 
        {
            $this->reset($stream,$bigEndian);
        }

        public function reset(InputStream $stream,$bigEndian) 
        {
            $this->m_stream = $stream;
            $this->m_bigEndian = $bigEndian;
            $this->m_position = 0;           
        }

        public function  close() 
        {
            if ($this->m_stream == null) {
                return;
            }
            try {
                $this->m_stream->close();
            }
            catch (IOException $e) {
            }

            $this->reset(null,false);
        }

        public function getStream() {
            return $this->m_stream;
        }

        public function isBigEndian() {
            return $this->m_bigEndian;
        }
        public function setBigEndian($bigEndian) 
        {
            $this->m_bigEndian = $bigEndian;
        }

        public function readByte()
        {
            return $this->_readInt(1);
        }
        public function readShort()  
        {
            return $this->_readInt(2);
        }
        public function readInt()
        {
            return $this->_readInt(4);
        }

        public function _readInt($length)
        {
            if ($length<0 || $length>4) 
                throw new IllegalArgumentException();

            if ($this->m_bigEndian) 
                return $this->m_stream->readIntEndian();
            else 
                return $this->m_stream->readInt();
        }

        public function readIntArray($length)
        {
            $array = array();
            $off = 0;
            $this->_readIntArray($array,$off,$length);
            return $array;
        }

        public function _readIntArray(&$array,&$offset,$length)
        {
            for (;$length > 0; $length -= 1) 
            {
                $array[$offset++] = $this->readInt();
            }
        }

        public function readByteArray($length)
        {
            return $this->m_stream->readByteArray($length);
        }   

        public function skip($bytes) 
        {
            if ($bytes <= 0) 
                return;

            $skipped = $this->m_stream->skip($bytes);
            $this->m_position += $skipped;
        }

        public function skipInt()
        {
            $this->skip(4);
        }

        public function available()
        {
            return $this->m_stream->available();
        }

        public function getPosition() {
            return $this->m_position;
        }

        /////////////////////////////////// data


    }

?>
