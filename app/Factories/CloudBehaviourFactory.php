<?php


namespace App\Factories;

use App\Behaviours\YandexDiskBehaviour;
use App\Behaviours\DropboxBehaviour;
use App\Interfaces\CloudBehaviourInterface;
use App\Singletons\DotenvSingleton;

class CloudBehaviourFactory
{
    const YANDEX_DISK = 1;
    const DROPBOX = 2;
    protected $type;

    public function __construct()
    {
        $dotenv = DotenvSingleton::getInstance();
        $this->type = $dotenv->getEnv('CLOUD_TYPE');
    }

    public function create(string $baseFolder): CloudBehaviourInterface
    {
        switch ($this->type) {
            case self::YANDEX_DISK:
                return new YandexDiskBehaviour($baseFolder);

            case self::DROPBOX:
                return new DropboxBehaviour($baseFolder);

            default:
                throw new \Error("Cloud behaviour not found");
        }
    }
}