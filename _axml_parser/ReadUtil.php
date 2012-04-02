<?php
    class ReadUtil 
    {

        public static function readCheckType(Stream $reader,$expectedType)
        {     
            if($reader instanceof IntReader)
                $type = $reader->readInt();
            else
                $type = self::readIntEndian($reader);
                
            if ($type != $expectedType) 
            {
                throw new IOException(
                    "Expected chunk of type 0x" . dechex($expectedType) . 
                    ", read 0x" . dechex($type) . ".");
            }
        }
        
        
        public static function readIntEndian(InputStream $stream)
        {
            return $stream->readIntEndian();
        }

        public static function readIntArray(InputStream $stream,$elementCount)
        {
            $result = array();
            for ($i = 0; $i != $elementCount; ++$i) 
            {
                $result[$i] = self::readInt($stream);
            }
            return $result;
        }

        public static function readInt(InputStream $stream)
        {
            return self::_readInt($stream,4);
        }

        public static function readShort(InputStream $stream)
        {
            return self::_readInt($stream,2);
        }

        public static function readString(InputStream $stream)
        {
            $length = self::readShort($stream);
            $builder = new StringBuilder();
            for ($i=0; $i != $length; ++$i) 
            {
                $builder->append(chr(self::readShort($stream)));
            }
            self::readShort($stream);
            return $builder->toString();
        }

        public static function _readInt(InputStream $stream,$length) 
        {
            $result = 0;
            for ($i=0; $i != $length; ++$i) 
            {
                $b = $stream->readByte();
                if ($b ==- 1) 
                {
                    throw new EOFException();
                }
                $result |= ($b << ($i*8));
            }
            return $result;          
        }

    }

