<?php
    class InputStream extends Stream
    {
        /**
        * file strem, like "fopen"
        * 
        * @var resource
        */
        private $stream;

        /**                                   
        * @param resource $stream File stream.
        * @return ApkStream
        */
        public function __construct($stream)
        {
            if(is_string($stream) && is_file($stream))
                $stream = fopen($stream,'rb');

            if(!is_resource($stream))
                // TODO : the resource type must be a regular file stream resource.
                throw new Exception( "Invalid stream" );

            $this->stream = $stream;
        }

        /**
        * Read the next character from stream.
        * 
        * @param mixed $length
        */
        public function read($length = 1)
        {
            return fread($this->stream,$length);
        }

        /**
        * check if end of filestream
        */
        public function feof()
        {
            return feof($this->stream);
        }

        /**
        * Jump to the index!
        * @param int $offset
        */
        public function seek($offset)
        {
            fseek($this->stream,$offset);
        }

        /**
        * Close the stream
        */
        public function close()
        {
            fclose($this->stream);
        }

        /**
        * Read the next byte
        * @return byte
        */
        public function readByte()
        {
            return ord($this->read());
        }

        public function skip($length)
        {
            $this->read($length);
        }

        /**
        * combine packs of bytes into int.
        * @return int
        */
        public function readInt()
        {
            $b = $this->getByteArray(4);
            
            return ($b[0] << 24)
            + (($b[1] & 0xFF) << 16)
            + (($b[2] & 0xFF) << 8)
            + ($b[3] & 0xFF);

        }
        
        
        public function readIntEndian()
        {
            $b = $this->getByteArray(4);
            
            $b = array_reverse($b);
            
            return ($b[0] << 24)
            + (($b[1] & 0xFF) << 16)
            + (($b[2] & 0xFF) << 8)
            + ($b[3] & 0xFF);
        }

        /**
        * fetch the remaining byte into an array
        * 
        * @param mixed $count Byte length.
        * @return array
        */
        public function getByteArray($length = null)
        {
            $bytes = array();

            while(!$this->feof() && ($length === null || count($bytes) < $length))
                $bytes[] = $this->readByte();

            return $bytes;
        }
    }
