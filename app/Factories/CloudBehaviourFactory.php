<?php


namespace App\Factories;

use App\Behaviours\YandexDiskBehaviour;
use App\Behaviours\DropboxBehaviour;
use App\Interfaces\CloudBehaviourInterface;

class CloudBehaviourFactory
{
    const YANDEX_DISK = 1;
    const DROPBOX = 2;

    public function create(int $type, string $baseFolder): CloudBehaviourInterface
    {
        switch ($type) {
            case self::YANDEX_DISK:
                return new YandexDiskBehaviour($baseFolder);

            case self::DROPBOX:
                return new DropboxBehaviour($baseFolder);

            default:
                throw new \Error("Cloud behaviour not found");
        }
    }
}