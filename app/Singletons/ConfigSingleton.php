<?php


namespace App\Singletons;

use Noodlehaus\Config;

class ConfigSingleton
{
    protected static $instance;
    protected $config;

    private function __construct()
    {
        $this->config = new Config(base_path('config.json'));
    }

    public static function getInstance(): self
    {
        if(self::$instance) {
            return self::$instance;
        }

        self::$instance = new self();

        return self::$instance;
    }

    public function get(string $name)
    {
        return $this->config->get($name);
    }
}