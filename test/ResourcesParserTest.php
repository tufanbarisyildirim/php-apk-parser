<?php

use ApkParser\ResourcesParser;
use ApkParser\SeekableStream;
use ApkParser\Archive;

class ResourcesParserTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessConfigParsesDpDimensionsAsInt16()
    {
        $configBytes = pack('V', 48) .                  // size
            pack('v', 310) . pack('v', 260) .          // mcc/mnc
            "enUS" .                                   // language/country
            pack('C', 1) . pack('C', 2) . pack('v', 480) . // orientation/touchscreen/density
            pack('C', 3) . pack('C', 4) . pack('C', 5) . pack('C', 0) . // keyboard/navigation/input
            pack('v', 1080) . pack('v', 1920) .        // screenWidth/screenHeight
            pack('v', 30) . pack('v', 1) .             // sdVersion/minorVersion
            pack('C', 2) . pack('C', 3) . pack('v', 600) . // screenLayout/uiMode/smallestScreenWidthDp
            pack('v', 360) . pack('v', 640) .          // screenWidthDp/screenHeightDp
            pack('V', 0x64617461) .                    // localeScript
            "POSIX123";                                // localeVariant (8 bytes)

        $streamResource = fopen('php://memory', 'wb+');
        fwrite($streamResource, $configBytes);
        rewind($streamResource);
        $stream = new SeekableStream($streamResource);

        $parserRef = new \ReflectionClass(ResourcesParser::class);
        $parser = $parserRef->newInstanceWithoutConstructor();
        $method = $parserRef->getMethod('processConfig');
        $method->setAccessible(true);

        $config = $method->invoke($parser, $stream);

        $this->assertSame(360, $config['screenWidthDp']);
        $this->assertSame(640, $config['screenHeightDp']);
        $this->assertSame(640 * 65536 + 360, $config['screenSizeDp']);

        fclose($streamResource);
    }

    public function testProcessStringPoolDecodesUtf8ExtendedLengthPrefix()
    {
        $longString = str_repeat('a', 130);
        $chunk = $this->buildStringPoolChunkUtf8(array('short', $longString));

        $strings = $this->invokePrivateParserMethod('processStringPool', $chunk);

        $this->assertSame(array('short', $longString), $strings);
    }

    public function testProcessStringPoolDecodesUtf16Strings()
    {
        $sourceStrings = array('Merhaba', 'Türkçe');
        $chunk = $this->buildStringPoolChunkUtf16($sourceStrings);

        $strings = $this->invokePrivateParserMethod('processStringPool', $chunk);

        $this->assertSame($sourceStrings, $strings);
    }

    /**
     * @dataProvider localizedApkVariantProvider
     * @param array $fixture
     * @throws \Exception
     */
    public function testFixtureBasedApkVariantsWithLocales(array $fixture)
    {
        $apkFile = $this->buildApkFixtureFromDefinition($fixture);
        $archive = null;

        try {
            $archive = new Archive($apkFile);
            $resourcesParser = new ResourcesParser($archive->getResourcesStream());

            $this->assertSame(
                $fixture['expected_values'],
                $resourcesParser->getResources($fixture['expected_resource_id'])
            );
        } finally {
            if ($archive instanceof Archive) {
                $archive->close();
            }
            if (is_string($apkFile) && file_exists($apkFile)) {
                unlink($apkFile);
            }
        }
    }

    /**
     * @return array
     */
    public function localizedApkVariantProvider()
    {
        $fixtureFile = __DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'resources_parser_apk_variants.json';
        $contents = file_get_contents($fixtureFile);
        if ($contents === false) {
            throw new \RuntimeException('Could not read fixture file: ' . $fixtureFile);
        }

        $fixtures = json_decode($contents, true);
        if (!is_array($fixtures)) {
            throw new \RuntimeException('Fixture file is not valid JSON: ' . $fixtureFile);
        }

        $cases = array();
        foreach ($fixtures as $fixture) {
            $cases[$fixture['name']] = array($fixture);
        }

        return $cases;
    }

    /**
     * @param string $method
     * @param string $chunkBytes
     * @return mixed
     */
    private function invokePrivateParserMethod($method, $chunkBytes)
    {
        $streamResource = fopen('php://memory', 'wb+');
        fwrite($streamResource, $chunkBytes);
        rewind($streamResource);

        try {
            $stream = new SeekableStream($streamResource);
            $parserRef = new \ReflectionClass(ResourcesParser::class);
            $parser = $parserRef->newInstanceWithoutConstructor();
            $refMethod = $parserRef->getMethod($method);
            $refMethod->setAccessible(true);

            return $refMethod->invoke($parser, $stream);
        } finally {
            fclose($streamResource);
        }
    }

    /**
     * @param array $fixture
     * @return string
     */
    private function buildApkFixtureFromDefinition(array $fixture)
    {
        $arsc = $this->buildResourceTableChunkFromFixture($fixture);
        $apkFile = tempnam(sys_get_temp_dir(), 'apk-resource-fixture-');
        if (!is_string($apkFile)) {
            throw new \RuntimeException('Could not create temporary apk fixture file');
        }

        $zip = new \ZipArchive();
        if ($zip->open($apkFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not open temporary apk fixture as zip');
        }

        $zip->addFromString('AndroidManifest.xml', 'placeholder');
        $zip->addFromString('resources.arsc', $arsc);
        $zip->close();

        return $apkFile;
    }

    /**
     * @param array $fixture
     * @return string
     */
    private function buildResourceTableChunkFromFixture(array $fixture)
    {
        $valueStringPool = $this->buildStringPoolChunkUtf8($fixture['value_strings']);
        $typeStringPool = $this->buildStringPoolChunkUtf8(array($fixture['type_name']));
        $keyStringPool = $this->buildStringPoolChunkUtf8(array($fixture['key_name']));

        $typeSpecChunk = $this->buildTypeSpecChunk((int)$fixture['type_id'], 1);

        $typeChunks = '';
        foreach ($fixture['entries'] as $entry) {
            $typeChunks .= $this->buildTypeChunk(
                (int)$fixture['type_id'],
                (int)$entry['value_index'],
                $entry['language'],
                $entry['country']
            );
        }

        $packageHeaderSize = 284;
        $packageId = (int)$fixture['package_id'];
        $packageName = str_repeat("\x00", 256);
        $typeStringsStart = $packageHeaderSize;
        $keyStringsStart = $typeStringsStart + strlen($typeStringPool);

        $packageBody = $typeStringPool . $keyStringPool . $typeSpecChunk . $typeChunks;
        $packageSize = $packageHeaderSize + strlen($packageBody);
        $packageChunk = pack('v', ResourcesParser::RES_TABLE_PACKAGE_TYPE) .
            pack('v', $packageHeaderSize) .
            pack('V', $packageSize) .
            pack('V', $packageId) .
            $packageName .
            pack('V', $typeStringsStart) .
            pack('V', 0) .
            pack('V', $keyStringsStart) .
            pack('V', 0) .
            $packageBody;

        $tableHeaderSize = 12;
        $tableSize = $tableHeaderSize + strlen($valueStringPool) + strlen($packageChunk);

        return pack('v', ResourcesParser::RES_TABLE_TYPE) .
            pack('v', $tableHeaderSize) .
            pack('V', $tableSize) .
            pack('V', 1) .
            $valueStringPool .
            $packageChunk;
    }

    /**
     * @param int $typeId
     * @param int $entriesCount
     * @return string
     */
    private function buildTypeSpecChunk($typeId, $entriesCount)
    {
        $headerSize = 16;
        $size = $headerSize + ($entriesCount * 4);

        $chunk = pack('v', ResourcesParser::RES_TABLE_TYPE_SPEC_TYPE) .
            pack('v', $headerSize) .
            pack('V', $size) .
            pack('C', $typeId) .
            pack('C', 0) .
            pack('v', 0) .
            pack('V', $entriesCount);

        for ($i = 0; $i < $entriesCount; $i++) {
            $chunk .= pack('V', 1);
        }

        return $chunk;
    }

    /**
     * @param int $typeId
     * @param int $valueIndex
     * @param string $language
     * @param string $country
     * @return string
     */
    private function buildTypeChunk($typeId, $valueIndex, $language, $country)
    {
        $entriesCount = 1;
        $config = $this->buildTypeConfig($language, $country);
        $configSize = strlen($config);
        $headerSize = 24 + $configSize;
        $entriesStart = $headerSize + ($entriesCount * 4);

        $entryData = pack('v', 8) .
            pack('v', 0) .
            pack('V', 0) .
            pack('v', 8) .
            pack('C', 0) .
            pack('C', ResourcesParser::TYPE_STRING) .
            pack('V', $valueIndex);

        $chunkSize = $entriesStart + strlen($entryData);

        return pack('v', ResourcesParser::RES_TABLE_TYPE_TYPE) .
            pack('v', $headerSize) .
            pack('V', $chunkSize) .
            pack('C', $typeId) .
            pack('C', 0) .
            pack('v', 0) .
            pack('V', $entriesCount) .
            pack('V', $entriesStart) .
            pack('V', $configSize) .
            $config .
            pack('V', 0) .
            $entryData;
    }

    /**
     * @param string $language
     * @param string $country
     * @return string
     */
    private function buildTypeConfig($language, $country)
    {
        $languageBytes = str_pad(substr($language, 0, 2), 2, "\x00");
        $countryBytes = str_pad(substr($country, 0, 2), 2, "\x00");

        return pack('V', 20) .
            pack('v', 0) .
            pack('v', 0) .
            $languageBytes .
            $countryBytes .
            pack('C', 0) .
            pack('C', 0) .
            pack('v', 0) .
            pack('C', 0) .
            pack('C', 0) .
            pack('C', 0) .
            pack('C', 0);
    }

    /**
     * @param array $strings
     * @return string
     */
    private function buildStringPoolChunkUtf8(array $strings)
    {
        $offsets = array();
        $stringData = '';

        foreach ($strings as $string) {
            $offsets[] = strlen($stringData);
            $utf16Length = mb_strlen($string, 'UTF-8');
            $byteLength = strlen($string);

            $stringData .= $this->encodeUtf8Length($utf16Length);
            $stringData .= $this->encodeUtf8Length($byteLength);
            $stringData .= $string . "\x00";
        }

        return $this->buildStringPoolChunk($offsets, $stringData, 256);
    }

    /**
     * @param array $strings
     * @return string
     */
    private function buildStringPoolChunkUtf16(array $strings)
    {
        $offsets = array();
        $stringData = '';

        foreach ($strings as $string) {
            $offsets[] = strlen($stringData);
            $charLength = mb_strlen($string, 'UTF-8');
            $stringData .= $this->encodeUtf16Length($charLength);
            $stringData .= mb_convert_encoding($string, 'UTF-16LE', 'UTF-8');
            $stringData .= "\x00\x00";
        }

        return $this->buildStringPoolChunk($offsets, $stringData, 0);
    }

    /**
     * @param array $offsets
     * @param string $stringData
     * @param int $flags
     * @return string
     */
    private function buildStringPoolChunk(array $offsets, $stringData, $flags)
    {
        $headerSize = 28;
        $stringsStart = $headerSize + (count($offsets) * 4);
        $size = $stringsStart + strlen($stringData);

        $chunk = pack('v', ResourcesParser::RES_STRING_POOL_TYPE) .
            pack('v', $headerSize) .
            pack('V', $size) .
            pack('V', count($offsets)) .
            pack('V', 0) .
            pack('V', $flags) .
            pack('V', $stringsStart) .
            pack('V', 0);

        foreach ($offsets as $offset) {
            $chunk .= pack('V', $offset);
        }

        return $chunk . $stringData;
    }

    /**
     * @param int $length
     * @return string
     */
    private function encodeUtf8Length($length)
    {
        if ($length < 0x80) {
            return chr($length);
        }

        return chr((($length >> 8) & 0x7f) | 0x80) . chr($length & 0xff);
    }

    /**
     * @param int $length
     * @return string
     */
    private function encodeUtf16Length($length)
    {
        if ($length < 0x8000) {
            return pack('v', $length);
        }

        return pack('v', (($length >> 16) & 0x7fff) | 0x8000) . pack('v', $length & 0xffff);
    }
}
