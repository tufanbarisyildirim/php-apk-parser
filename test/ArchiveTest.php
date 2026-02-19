<?php

use ApkParser\Archive;
use ApkParser\Exceptions\ApkException;
use ApkParser\Exceptions\FileNotFoundException;

class ArchiveTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructorThrowsFileNotFoundExceptionForMissingFile()
    {
        $this->expectException(FileNotFoundException::class);

        new Archive(__DIR__ . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'missing.apk');
    }

    public function testConstructorThrowsApkExceptionForInvalidZip()
    {
        $file = tempnam(sys_get_temp_dir(), 'invalid-apk-');
        $this->assertTrue(is_string($file));

        try {
            file_put_contents($file, 'not-a-valid-zip');

            $this->expectException(ApkException::class);
            new Archive($file);
        } finally {
            if (is_string($file) && file_exists($file)) {
                unlink($file);
            }
        }
    }
}
