<?php

namespace ApkParser;

use ApkParser\Exceptions\ApkException;

/**
 * This file is part of the Apk Parser package.
 *
 * (c) Tufan Baris Yildirim <tufanbarisyildirim@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Parser
{
    private $apk;
    private $manifest;
    /**
     * @var ResourcesParser|null
     */
    private $resources;
    private $config;

    /**
     * @param $apkFile
     * @param array $config
     * @throws \Exception
     */
    public function __construct($apkFile, array $config = [])
    {
        $this->config = new Config($config);
        $this->apk = new Archive($apkFile);
        $this->manifest = new Manifest(new XmlParser($this->apk->getManifestStream()));
        $isManifestOnly = (bool)$this->config->get('manifest_only');

        if (!$isManifestOnly) {
            $this->resources = new ResourcesParser($this->apk->getResourcesStream());
        } else {
            $this->resources = null;
        }
    }

    /**
     * Get Manifest Object
     * @return \ApkParser\Manifest
     */
    public function getManifest()
    {
        return $this->manifest;
    }

    /**
     * Get the apk. Zip handler.
     * - Extract all(or sp. entries) files,
     * - add file,
     * - recompress
     * - and other ZipArchive features.
     *
     * @return \ApkParser\Archive
     */
    public function getApkArchive()
    {
        return $this->apk;
    }

    /**
     * @param $key
     * @return bool|mixed
     */
    public function getResources($key)
    {
        return is_null($this->resources) ? false : $this->resources->getResources($key);
    }

    /**
     * Get all resources as an array
     */
    public function getAllResources()
    {
        return is_null($this->resources) ? [] : $this->resources->getAllResources();
    }

    /**
     * @param $name
     * @return resource
     */
    public function getStream($name)
    {
        return $this->apk->getStream($name);
    }

    /**
     * Extract apk content directly
     *
     * @param mixed $destination
     * @param array $entries
     * @return bool
     */
    public function extractTo($destination, $entries = null)
    {
        return $this->apk->extractTo($destination, $entries);
    }

    /**
     * @return array
     * @throws ApkException
     * @throws \Exception
     */
    public function getClasses()
    {
        $dexStream = $this->apk->getClassesDexStream();
        $apkName = $this->apk->getApkName();
        $jarPath = $this->config->get('jar_path');
        $javaBinary = $this->config->get('java_binary');

        $cache_folder = $this->config->tmp_path . '/' . str_replace('.', '_', $apkName) . '/';

        // No folder means no cached data.
        if (!is_dir($cache_folder)) {
            mkdir($cache_folder, 0755, true);
        }

        if (!is_file($jarPath) || !is_readable($jarPath)) {
            throw new ApkException('Decompiler jar file not found or not readable: ' . $jarPath);
        }
        $this->assertJavaBinaryAvailable($javaBinary);

        $dex_file = $cache_folder . '/classes.dex';
        $dexStream->save($dex_file);

        // Extract dalvik compiled codes to the cache folder.
        $command = array($javaBinary, '-jar', $jarPath, '-d', $cache_folder, $dex_file);
        list($exitCode, $stdout, $stderr) = $this->runCommand($command);

        if ($exitCode !== 0) {
            $message = "Couldn't decompile .dex file";
            $details = trim($stderr) !== '' ? trim($stderr) : trim($stdout);
            if ($details !== '') {
                $message .= ': ' . $details;
            }
            throw new ApkException($message . " (exit code: {$exitCode})");
        }

        $file_list = Utils::globRecursive($cache_folder . '*.ddx');

        //Make classnames more readable.
        foreach ($file_list as &$file) {
            $file = str_replace($cache_folder, '', $file);
            $file = str_replace('/', '.', $file);
            $file = str_replace('.ddx', '', $file);
            $file = trim($file, '.');
        }


        return $file_list;
    }

    /**
     * @param string $javaBinary
     * @throws ApkException
     */
    private function assertJavaBinaryAvailable($javaBinary)
    {
        if (!is_string($javaBinary) || trim($javaBinary) === '') {
            throw new ApkException('Invalid Java binary configuration');
        }

        list($exitCode, ) = $this->runCommand(array($javaBinary, '-version'));
        if ($exitCode !== 0) {
            throw new ApkException('Java binary is not available: ' . $javaBinary);
        }
    }

    /**
     * @param array $command
     * @return array
     * @throws ApkException
     */
    private function runCommand(array $command)
    {
        $descriptorSpec = array(
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $process = proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            throw new ApkException('Unable to start process');
        }

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return array($exitCode, (string)$stdout, (string)$stderr);
    }
}
