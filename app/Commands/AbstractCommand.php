<?php


namespace App\Commands;

use App\Factories\CloudBehaviourFactory;
use App\Singletons\LoggerSingleton;
use App\Singletons\ConfigSingleton;

class AbstractCommand
{
    protected $cloudStorage;
    protected $logger;
    protected $config;

    public function __construct()
    {
        $this->logger = LoggerSingleton::getInstance();
        $this->config = ConfigSingleton::getInstance();

        $cloudStorageFolder = $this->config->get($this->cloudStorageFolderConfig);
        $this->cloudStorage = (new CloudBehaviourFactory())->create($cloudStorageFolder);
    }
}
