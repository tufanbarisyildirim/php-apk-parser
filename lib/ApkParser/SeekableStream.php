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

class SeekableStream
{
    const LITTLE_ENDIAN_ORDER = 1;
    const BIG_ENDIAN_ORDER = 2;
    /**
     * The endianess of the current machine.
     *
     * @var integer
     */
    private static $endianess = 0;
    private $stream;
    private $size = 0;

    public function __construct($stream = null)
    {
        if (is_null($stream) || is_resource($stream) === false) {
            throw new \InvalidArgumentException('Stream must be a resource');
        }
        $meta = stream_get_meta_data($stream);
        if ($meta['seekable'] === false) {
            $this->stream = self::toMemoryStream($stream);
        } else {
            $this->stream = $stream;
        }
        rewind($this->stream);
        fseek($this->stream, 0, SEEK_END);
        $this->size = ftell($this->stream);
        rewind($this->stream);
    }

    /**
     * @param $length
     * @return SeekableStream
     */
    public function copyBytes($length)
    {
        return new self(self::toMemoryStream($this->stream, $length));
    }

    /**
     * Obtain a number of bytes from the string
     *
     * @throws \RuntimeException
     * @param int $length
     * @return string
     */
    public function read($length = 1)
    {
        // Protect against 0 byte reads when an EOF
        if ($length < 0) {
            throw new \RuntimeException('Length cannot be negative');
        }
        if ($length == 0) return '';

        $bytes = fread($this->stream, $length);
        if (FALSE === $bytes || strlen($bytes) != $length) {
            throw new \RuntimeException('Failed to read ' . $length . ' bytes');
        }
        return $bytes;
    }

    public function seek($offset)
    {
        fseek($this->stream, $offset);
    }

    /**
     * Check if we have reached the end of the stream
     *
     * @return bool
     */
    public function eof()
    {
        return feof($this->stream);
    }

    /**
     * Obtain the current position in the stream
     *
     * @return int
     */
    public function position()
    {
        return ftell($this->stream);
    }

    /**
     * @return int
     */
    public function size()
    {
        return $this->size;
    }

    /**
     * @return int
     */
    public function readByte()
    {
        return ord($this->read(1));
    }

    /**
     * Reads 2 bytes from the stream and returns little-endian ordered binary
     * data as signed 16-bit integer.
     *
     * @return integer
     */
    public function readInt16LE()
    {
        if (self::isBigEndian()) {
            return self::unpackInt16(strrev($this->read(2)));
        } else {
            return self::unpackInt16($this->read(2));
        }
    }

    /**
     * Reads 4 bytes from the stream and returns little-endian ordered binary
     * data as signed 32-bit integer.
     *
     * @return integer
     */
    public function readInt32LE()
    {
        if (self::isBigEndian()) {
            return self::unpackInt32(strrev($this->read(4)));
        } else {
            return self::unpackInt32($this->read(4));
        }
    }

    /**
     * Returns machine endian ordered binary data as signed 16-bit integer.
     *
     * @param string $value The binary data string.
     * @return integer
     */
    private static function unpackInt16($value)
    {
        list(, $int) = unpack('s*', $value);
        return $int;
    }

    /**
     * Returns machine-endian ordered binary data as signed 32-bit integer.
     *
     * @param string $value The binary data string.
     * @return integer
     */
    private static function unpackInt32($value)
    {
        list(, $int) = unpack('l*', $value);
        return $int;
    }

    /**
     * Returns the current machine endian order.
     * @return integer
     */
    private static function getEndianess()
    {
        if (self::$endianess === 0) {
            self::$endianess = self::unpackInt32("\x01\x00\x00\x00") == 1 ? self::LITTLE_ENDIAN_ORDER : self::BIG_ENDIAN_ORDER;
        }
        return self::$endianess;
    }

    /**
     * Returns whether the current machine endian order is big endian.
     * @return boolean
     */
    private static function isBigEndian()
    {
        return self::getEndianess() == self::BIG_ENDIAN_ORDER;
    }

    /**
     * @param $stream
     * @param int $length
     * @return resource
     */
    private static function toMemoryStream($stream, $length = 0)
    {
        $size = 0;
        $memoryStream = fopen('php://memory', 'wb+');
        while (!feof($stream)) {
            fputs($memoryStream, fread($stream, 1));
            $size++;
            if ($length > 0 && $size >= $length) {
                break;
            }
        }
        return $memoryStream;
    }
}