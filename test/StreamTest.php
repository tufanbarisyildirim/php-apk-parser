<?php

use ApkParser\Stream;

class StreamTest extends \PHPUnit\Framework\TestCase
{
    public function testSaveToFilePathWritesAllBytes()
    {
        $sourceResource = fopen('php://memory', 'w+');
        fwrite($sourceResource, 'apk-parser-stream');
        rewind($sourceResource);

        $sourceStream = new Stream($sourceResource);
        $destinationPath = tempnam(sys_get_temp_dir(), 'stream-save-');
        $this->assertTrue(is_string($destinationPath));

        try {
            $sourceStream->save($destinationPath);
            $this->assertSame('apk-parser-stream', file_get_contents($destinationPath));
        } finally {
            fclose($sourceResource);
            if (is_string($destinationPath) && file_exists($destinationPath)) {
                unlink($destinationPath);
            }
        }
    }

    public function testSaveToProvidedResourceKeepsDestinationOpen()
    {
        $sourceResource = fopen('php://memory', 'w+');
        fwrite($sourceResource, 'resource-destination');
        rewind($sourceResource);

        $destinationResource = fopen('php://memory', 'w+');
        $sourceStream = new Stream($sourceResource);

        try {
            $sourceStream->save($destinationResource);
            $this->assertTrue(is_resource($destinationResource));

            rewind($destinationResource);
            $this->assertSame('resource-destination', stream_get_contents($destinationResource));
        } finally {
            fclose($sourceResource);
            if (is_resource($destinationResource)) {
                fclose($destinationResource);
            }
        }
    }
}
