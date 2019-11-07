<?php


namespace App\Behaviours;

use App\Singletons\LoggerSingleton;
use App\Interfaces\CloudBehaviourInterface;
use \Yandex\Disk\DiskClient;

class YandexDiskBehaviour implements CloudBehaviourInterface
{
    protected $diskClient;
    protected $baseFolder;
    protected $logger;

    public function __construct(string $baseFolder)
    {
        $this->logger = LoggerSingleton::getInstance();

        $token = getenv("YANDEX_DISK_TOKEN");

        $this->connect($token);
        $this->setBaseFolder($baseFolder);
    }

    protected function connect(string $token): void
    {
        $this->diskClient = new DiskClient($token);
        $this->diskClient->setServiceScheme(DiskClient::HTTPS_SCHEME);
    }

    protected function setBaseFolder(string $folder): void
    {
        $this->baseFolder = $folder;
    }

    protected function checkFolder(string $folder): bool
    {
        try {
            $dirContents = $this->diskClient->directoryContents($this->baseFolder);
        } catch (\Exception $e) {
            $this->logger->exitWithError("Error getting list of files in folder($this->baseFolder) on Yandex.Disk ".$e->getMessage());
        }

        foreach ($dirContents as $item) {
            if ($item['resourceType'] === 'dir' && $item['displayName'] === $folder) {
                //if the folder already exists
                return true;
            }
        }

        return false;
    }

    protected function createFolder(string $folder): void
    {
        $path = $this->baseFolder.DIRECTORY_SEPARATOR.$folder;

        $isExisted = $this->checkFolder($folder);

        if($isExisted) {
            return;
        }

        try {
            $this->diskClient->createDirectory($path);
            $this->logger->debug("Successful created the folder($path) on Yandex.Disk");
        } catch (\Exception $e) {
            $this->logger->exitWithError("Error creating folder on Yandex.Disk ".$e->getMessage());
        }
    }

    protected function checkFreeSpace(int $neededSize): bool
    {
        try {
            $diskSpace = $this->diskClient->diskSpaceInfo();
        } catch (\Exception $e) {
            $this->logger->exitWithError("Error getting information about free space on Yandex.Disk ".$e->getMessage());
        }

        $this->logger->debug("Checking free space Yandex.Disk. Free: {$diskSpace['availableBytes']} bytes");

        if($diskSpace['availableBytes'] < $neededSize) {
            return false;
        }

        return true;
    }

    protected function getOldestBackup(string $folder): string
    {
        try {
            $dirContent = $this->diskClient->directoryContents($folder);
        } catch (\Exception $e) {
            $this->logger->exitWithError("Error getting list of files in Yandex.Disk directory ".$e->getMessage());
        }

        $oldestFolder = '';
        $oldestFolderCreateDate = strtotime("now");

        foreach ($dirContent as $dirItem) {
            if ($dirItem['resourceType'] === 'dir' && $dirItem['displayName'] !== $folder) {
                if(strtotime($dirItem["creationDate"]) < $oldestFolderCreateDate) {
                    $oldestFolderCreateDate = strtotime($dirItem["creationDate"]);
                    $oldestFolder = $dirItem["href"];
                }
            }
        }

        if(empty($oldestFolder)) {
            $this->logger->exitWithError("Failed to get the oldest backup on Yandex.Disk");
        }

        return $oldestFolder;
    }

    protected function prepareFreeSpace(int $neededSize): void
    {
        $freeSpaceAvailable = $this->checkFreeSpace($neededSize);

        if($freeSpaceAvailable) {
            return ;
        }

        if($neededSize >= (getenv("CLOUD_DELETE_LIMIT_GB") * pow(1024,3))){
            $this->logger->exitWithError("Delete limit exceeded");
        }

        $pathToOldestBackup = $this->getOldestBackup($this->baseFolder);

        $this->delete($pathToOldestBackup);

        $this->prepareFreeSpace($neededSize);
    }

    public function upload(string $file, string $folder): void
    {
        if(!is_file($file)) {
            $this->logger->exitWithError("File for upload not found. Path: {$file}");
        }

        $fileName = basename($file);
        $fileSize = filesize($file);

        $this->createFolder($folder);

        $this->prepareFreeSpace($fileSize);

        try {
            $this->diskClient->uploadFile(
                DIRECTORY_SEPARATOR.$this->baseFolder.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR,
                [
                    'path' => $file,
                    'size' => $fileSize,
                    'name' => $fileName
                ]
            );
            $this->logger->debug("File ({$fileName}) was successfully uploaded to Yandex.Disk");
        } catch (\Exception $e) {
            $this->logger->exitWithError("Error loading for backup on Yandex.Disk ".$e->getMessage());
        }
    }

    public function delete(string $path): void
    {
        try {
            $this->diskClient->delete($path);
            $this->logger->debug("Successfully deleted the file({$path}) from Yandex.Disk");
        } catch (\Exception $e) {
            $this->logger->exitWithError("Error deleting file({$path}) on Yandex.Disk ".$e->getMessage());
        }
    }
}