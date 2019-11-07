<?php


namespace App\Behaviours;


use App\Interfaces\CloudBehaviourInterface;
use Kunnu\Dropbox\DropboxApp;

class DropboxBehaviour implements CloudBehaviourInterface
{
    protected $dropboxClient;
    protected $baseFolder;

    public function __construct(string $folder)
    {
        $this->dropboxClient = new DropboxApp("i6y3fth3zz5mebb", "jqpb96ctazac5yi");
    }

    protected function connect(string $token): void
    {

    }

    protected function setBaseFolder(string $folder): void
    {

    }

    protected function checkFolder(string $folder): bool
    {

    }

    protected function createFolder(string $folder): void
    {

    }

    protected function checkFreeSpace(int $neededSize): bool
    {

    }

    protected function getOldestBackup(string $folder): string
    {

    }

    protected function prepareFreeSpace(int $neededSize): void
    {

    }

    public function upload(string $file, string $folder): void
    {

    }

    public function delete(string $path): void
    {

    }
}