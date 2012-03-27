<?php
    class ApkStream
    {
        private $stream;
        
        public function __construct($stream)
        {
            if(!is_resource($stream))
                throw new Exception( "Invalid stream" );

            $this->stream = $stream;
        }

        public function read($length = 1)
        {
            return fread($this->stream,$length);
        }

        public function feof()
        {
            return feof($this->stream);
        }

        public function seek($offset)
        {
            fseek($this->stream,$offset);
        }

        public function close()
        {
            fclose($this->stream);
        }

        public function readByte()
        {
            return ord($this->read());
        }

        public function getByteArray()
        {
            $bytes = array();

            while(!$this->feof())
                $bytes[] = $this->readByte();

            return $bytes;
        }
    }

