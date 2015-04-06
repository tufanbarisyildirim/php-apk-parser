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

class ResourcesParser
{
    const RES_STRING_POOL_TYPE = 0x0001;
    const RES_TABLE_TYPE = 0x0002;
    const RES_TABLE_PACKAGE_TYPE = 0x0200;
    const RES_TABLE_TYPE_TYPE = 0x0201;
    const RES_TABLE_TYPE_SPEC_TYPE = 0x0202;
    // The 'data' holds a ResTable_ref, a reference to another resource table entry.
    const TYPE_REFERENCE = 0x01;
    // The 'data' holds an index into the containing resource table's global value string pool.
    const TYPE_STRING = 0x03;
    const FLAG_COMPLEX = 0x0001;

    /**
     * @var SeekableStream
     */
    private $stream;

    private $valueStringPool;
    private $typeStringPool;
    private $keyStringPool;

    private $packageId = 0;
    private $resources = array();

    /**
     * @param SeekableStream $stream
     * @throws \Exception
     */
    public function __construct(SeekableStream $stream)
    {
        $this->stream = $stream;
        $this->decompress();
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getResources($key)
    {
        return $this->resources[strtolower($key)];
    }

    /**
     * @throws \Exception
     */
    private function decompress()
    {
        $type = $this->stream->readInt16LE();
        $this->stream->readInt16LE(); // headerSize
        $size = $this->stream->readInt32LE();
        $packagesCount = $this->stream->readInt32LE();

        if ($type != self::RES_TABLE_TYPE) {
            throw new \Exception('No RES_TABLE_TYPE found');
        }
        if ($size != $this->stream->size()) {
            throw new \Exception('The buffer size not matches to the resource table size');
        }

        $realStringsCount = 0;
        $realPackagesCount = 0;
        while (true) {
            $pos = $this->stream->position();
            $chunkType = $this->stream->readInt16LE();
            $this->stream->readInt16LE(); // headerSize
            $chunkSize = $this->stream->readInt32LE();
            if ($chunkType == self::RES_STRING_POOL_TYPE) {
                // Only the first string pool is processed.
                if ($realStringsCount == 0) {
                    $this->stream->seek($pos);
                    $this->valueStringPool = $this->processStringPool($this->stream->copyBytes($chunkSize));
                }
                $realStringsCount++;
            } else if ($chunkType == self::RES_TABLE_PACKAGE_TYPE) {
                $this->stream->seek($pos);
                $this->processPackage($this->stream->copyBytes($chunkSize));
                $realPackagesCount++;
            } else {
                throw new \Exception('Unsupported Type');
            }

            $this->stream->seek($pos + $chunkSize);
            if ($this->stream->position() == $size) {
                break;
            }
        }
        if ($realStringsCount != 1) {
            throw new \Exception('More than 1 string pool found!');
        }
        if ($realPackagesCount != $packagesCount) {
            throw new \Exception('Real package count not equals the declared count.');
        }
    }

    /**
     * @param SeekableStream $data
     * @throws \Exception
     */
    private function processPackage(SeekableStream $data)
    {
        $data->readInt16LE(); // type
        $headerSize = $data->readInt16LE();
        $data->readInt32LE(); // size

        $this->packageId = $data->readInt32LE();
        $packageName = $data->read(256);
        // echo 'Package name: ', $packageName, PHP_EOL;

        $typeStringsStart = $data->readInt32LE();
        $data->readInt32LE(); // lastPublicType
        $keyStringsStart = $data->readInt32LE();
        $data->readInt32LE(); // lastPublicKey

        if ($typeStringsStart != $headerSize) {
            throw new \Exception('TypeStrings must immediately follow the package structure header.');
        }

        // echo 'Type strings', PHP_EOL;
        $data->seek($typeStringsStart);
        $this->typeStringPool = $this->processStringPool($data->copyBytes($data->size() - $data->position()));

        // echo 'Key strings', PHP_EOL;
        $data->seek($keyStringsStart);
        $data->readInt16LE(); // key_type
        $data->readInt16LE(); // key_headerSize
        $keySize = $data->readInt32LE();

        $data->seek($keyStringsStart);
        $this->keyStringPool = $this->processStringPool($data->copyBytes($data->size() - $data->position()));

        $data->seek($keyStringsStart + $keySize);

        // Iterate through all chunks
        while (true) {
            $pos = $data->position();
            $chunkType = $data->readInt16LE();
            $data->readInt16LE(); // headerSize
            $chunkSize = $data->readInt32LE();
            if ($chunkType == self::RES_TABLE_TYPE_SPEC_TYPE) {
                $data->seek($pos);
                $this->processTypeSpec($data->copyBytes($chunkSize));
            } else if ($chunkType == self::RES_TABLE_TYPE_TYPE) {
                $data->seek($pos);
                $this->processType($data->copyBytes($chunkSize));
            }

            $data->seek($pos + $chunkSize);
            if ($data->position() == $data->size()) {
                break;
            }
        }
    }

    /**
     * @param SeekableStream $data
     * @return array
     */
    private function processStringPool(SeekableStream $data)
    {
        $data->readInt16LE(); // type
        $data->readInt16LE(); // headerSize
        $data->readInt32LE(); // size
        $stringsCount = $data->readInt32LE();
        $data->readInt32LE(); // stylesCount
        $flags = $data->readInt32LE();
        $stringsStart = $data->readInt32LE();
        $data->readInt32LE(); // stylesStart

        $offsets = array();
        for ($i = 0; $i < $stringsCount; $i++) {
            $offsets[$i] = $data->readInt32LE();
        }
        $isUtf8 = ($flags & 256) != 0;

        $strings = array();
        for ($i = 0; $i < $stringsCount; $i++) {
            $lastPosition = $data->position();
            $pos = $stringsStart + $offsets[$i];
            $data->seek($pos);
            $len = $data->position();
            $data->seek($lastPosition);
            if ($len < 0) {
                $data->readInt16LE(); // extendShort
            }
            $pos += 2;

            $strings[$i] = '';
            if ($isUtf8) {
                $length = 0;
                $data->seek($pos);
                while ($data->readByte() != 0) {
                    $length++;
                }
                if ($length > 0) {
                    $data->seek($pos);
                    $strings[$i] = $data->read($length);
                } else {
                    $strings[$i] = '';
                }
            } else {
                $data->seek($pos);
                while (($c = $data->read()) != 0) {
                    $strings[$i] .= $c;
                    $pos += 2;
                }
            }
            // echo 'Parsed value: ', $strings[$i], PHP_EOL;
        }
        return $strings;
    }

    /**
     * @param SeekableStream $data
     */
    private function processTypeSpec(SeekableStream $data)
    {
        $data->readInt16LE(); // type
        $data->readInt16LE(); // headerSize
        $data->readInt32LE(); // size
        $id = $data->readByte();
        $data->readByte(); // res0
        $data->readInt16LE(); // res1
        $entriesCount = $data->readInt32LE();

        // echo 'Processing type spec ' . $this->typeStringPool[$id - 1], PHP_EOL;
        $flags = array();
        for ($i = 0; $i < $entriesCount; ++$i) {
            $flags[$i] = $data->readInt32LE();
        }
    }

    /**
     * @param SeekableStream $data
     * @throws \Exception
     */
    private function processType(SeekableStream $data)
    {
        $data->readInt16LE(); // type
        $headerSize = $data->readInt16LE();
        $data->readInt32LE(); // size
        $id = $data->readByte();
        $data->readByte(); // res0
        $data->readInt16LE(); // res1
        $entriesCount = $data->readInt32LE();
        $entriesStart = $data->readInt32LE();
        $data->readInt32LE(); // config_size

        if ($headerSize + $entriesCount * 4 != $entriesStart) {
            throw new \Exception('HeaderSize, entriesCount and entriesStart are not valid.');
        }

        // Skip the config data
        $data->seek($headerSize);

        // Start to get entry indices
        $entryIndices = array();
        for ($i = 0; $i < $entriesCount; ++$i) {
            $entryIndices[$i] = $data->readInt32LE();
        }

        // Get entries
        for ($i = 0; $i < $entriesCount; ++$i) {
            if ($entryIndices[$i] == -1) {
                continue;
            }

            $resourceId = ($this->packageId << 24) | ($id << 16) | $i;

            $data->readInt16LE(); // entry_size
            $entryFlag = $data->readInt16LE();
            $entryKey = $data->readInt32LE();

            $resourceIdString = '0x' . dechex($resourceId);
            $entryKeyString = $this->keyStringPool[$entryKey];
            // echo 'Entry ' . $resourceIdString . ', key: ' . $entryKeyString;

            // Get the value (simple) or map (complex)
            if (($entryFlag & self::FLAG_COMPLEX) == 0) {
                // echo ', simple value type';
                // Simple case
                $data->readInt16LE(); // value_size
                $data->readByte(); // value_res0
                $valueDataType = $data->readByte();
                $valueData = $data->readInt32LE();

                if ($valueDataType == self::TYPE_STRING) {
                    $value = $this->valueStringPool[$valueData];
                    $this->putResource($resourceIdString, $value);
                    // echo ', data: ' . $value;
                } else if ($valueDataType == self::TYPE_REFERENCE) {
                    $referenceIdString = '0x' . dechex($valueData);
                    $this->putReferenceResource($resourceIdString, $referenceIdString);
                    // echo ', reference: ' . $referenceIdString;
                } else {
                    $this->putResource($resourceIdString, $valueData);
                    // echo ', data: ' . $valueData;
                }
                // echo PHP_EOL;
            } else {
                // echo ', complex value, not printed.', PHP_EOL;
                $data->readInt32LE(); // entry_parent
                $entryCount = $data->readInt32LE();
                for ($j = 0; $j < $entryCount; ++$j) {
                    $data->readInt32LE(); // ref_name
                    $data->readInt16LE(); // value_size
                    $data->readByte(); // value_res0
                    $data->readByte(); // value_data_type
                    $data->readInt32LE(); // value_data
                }
            }
        }
    }

    /**
     * @param $resourceId
     * @param $value
     */
    private function putResource($resourceId, $value)
    {
        $key = strtolower($resourceId);
        if (array_key_exists($key, $this->resources) === false) {
            $this->resources[$key] = array();
        }
        $this->resources[$key][] = $value;
    }

    /**
     * @param $resourceId
     * @param $valueData
     */
    private function putReferenceResource($resourceId, $valueData)
    {
        $key = strtolower($resourceId);
        if (array_key_exists($key, $this->resources)) {
            $values = $this->resources[$key];
            foreach ($values as $value) {
                $this->putResource($valueData, $value);
            }
        }
    }
}