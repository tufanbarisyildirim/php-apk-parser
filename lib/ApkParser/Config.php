<?php
namespace ApkParser;

class Config
{
    private $config;

    public function __construct(array $config = null)
    {
        if ($config == null) {
            // set default configs
            $config = array(
                'tmp_path' => '/tmp',
                'jar_path' => dirname(__FILE__) . '/Dex/dedexer.jar'
            );
        }

        $this->config = $config;
    }

    public function get($key)
    {
        return $this->config[$key];
    }
}