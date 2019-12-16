<?php
namespace App\Commands;

use App\Interfaces\CommandInterface;
use App\Factories\CloudBehaviourFactory;
use App\Singletons\LoggerSingleton;
use App\Singletons\ConfigSingleton;

class FilesBackupCommand implements CommandInterface {
    protected $cloudStorage;
    protected $logger;
    protected $config;
    protected $files;

    public function __construct()
    {
        $this->logger = LoggerSingleton::getInstance();
        $this->config = ConfigSingleton::getInstance();
        $this->files = $this->config->get('files');

        $baseFolder = $this->config->get("FILES_DUMP_FOLDER");

        $factory = new CloudBehaviourFactory();

        $this->cloudStorage = $factory->create($baseFolder);
    }

    public function execute(): void
    {
        foreach ($this->files as $file) {
            //создаем архив
            $pathToArchive = $this->buildPathToArchive($file['entry']);
            $this->createArchive($file, $pathToArchive);

            //upload archive
            $folderToUpload = date("d.m.Y");
            $this->cloudStorage->upload($pathToArchive, $folderToUpload);

            //удаляем архив с дампом с сервера
            unlink($pathToArchive);
        }

        $this->logger->sendEmailWithLog("Backup files created");
    }

    protected function buildPathToArchive($path): string
    {
        $fileName = basename($path);
        $pathToArchive = base_path("storage/backups/{$fileName}.tar.gz");

        return $pathToArchive;
    }

    protected function createArchive($pathToFile, $pathToArchive)
    {
        exec("zip -r -T {$pathToArchive} {$pathToFile}", $output, $return_var);

        if($return_var) {
            $this->logger->exitWithError("Failed to create archive {$pathToFile}. Error code {$return_var}");
        }

        $this->logger->debug("Archive of {$pathToFile} was created");
    }
}



//    foreach ($this->config->files as $item) {
//
//        $excludeString = '';
//        foreach ($item->excluded as $excludedItem) {
//            $excludeString .= "--exclude \"{$excludedItem}\" ";
//        }
//
//        $pathForNewArchive = $this->pathForBackup . basename($item->entry) . '.tar.gz';
//        $command = "tar czf {$pathForNewArchive} {$excludeString} {$item->entry} --warning=no-file-changed";
//
//        //создаем архив
//        exec($command);
//
//        //определяем размер архива, чтобы освоболить под него место на яндекс диске
//        $fileSize = filesize($pathForNewArchive);
//        //определяем свобоное место на диске
//        $this->checkFreeSpaceOnYandexDisk($fileSize, $this->WEBDAV_MININMUM_FREE_SPACE_GB, $this->baseFolderForCommandOnYandexDisk);
//        //загружаем архив дампа на яндекс диск
//        $this->uploadToYandexDisk($pathForNewArchive, $pathToUpload);
//        //удаляем архив с дампом с сервера
//        unlink($pathForNewArchive);
//    }
//
//    $this->sendEmailWithLog("Бэкап файлов создан");
