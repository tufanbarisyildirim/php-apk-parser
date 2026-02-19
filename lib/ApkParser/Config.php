<?php
/**
 * This file is part of the Apk Parser package.
 *
 * (c) Tufan Baris Yildirim <tufanbarisyildirim@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApkParser;

/**
 * Class Config
 * @package ApkParser
 * @property $tmp_path string
 * @property $jar_path string
 * @property $java_binary string
 * @property $manifest_only boolean
 */
class Config
{
    /**
     * @var array
     */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge(
            [
                'tmp_path' => sys_get_temp_dir(),
                'jar_path' => __DIR__ . '/Dex/dedexer.jar',
                'java_binary' => 'java',
                'manifest_only' => true
            ],
            $config
        );
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return array_key_exists($key, $this->config) ? $this->config[$key] : null;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return array_key_exists($key, $this->config) ? $this->config[$key] : null;
    }

    /**
     * @param $name
     * @param $value
     * @return mixed
     * @internal param $key
     */
    public function __set($name, $value)
    {
        return $this->config[$name] = $value;
    }
}
