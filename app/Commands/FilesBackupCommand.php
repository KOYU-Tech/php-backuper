<?php
namespace App\Commands;

use App\Interfaces\CommandInterface;
use App\Factories\CloudBehaviourFactory;
use App\Singletons\LoggerSingleton;

class FilesBackupCommand implements CommandInterface {
    protected $cloudStorage;
    protected $logger;

    public function __construct()
    {
        $this->logger = LoggerSingleton::getInstance();

        $type = getenv("CLOUD_TYPE");
        $baseFolder = getenv("FILES_DUMP_FOLDER");

        $factory = new CloudBehaviourFactory();

        $this->cloudStorage = $factory->create($type, $baseFolder);
    }

    public function execute(): void
    {
        $files = $this->getFilesForBackup(getenv("FILES_FOLDER"), getenv("EXCLUDE_FILES"));

        foreach ($files as $file) {
            //создаем архив
            $pathToArchive = $this->buildPathToArchive($file);
            $this->createArchive($file, $pathToArchive);

            //upload archive
            $folderToUpload = date("d.m.Y");
            $this->cloudStorage->upload($pathToArchive, $folderToUpload);

            //удаляем архив с дампом с сервера
            unlink($pathToArchive);
        }

        $this->logger->sendEmailWithLog("Backup files created");
    }

    protected function getFilesForBackup($filesFolder, $excludedFilesString)
    {
        $excludedFiles = explode(",", $excludedFilesString);
        $excludedFiles = array_map('trim', $excludedFiles);
        array_push($excludedFiles, '..');
        array_push($excludedFiles, '.');
        array_push($excludedFiles, base_folder());

        $files = array_diff(scandir($filesFolder), $excludedFiles);
        $files = array_map(function ($str) use($filesFolder) {
            return realpath("{$filesFolder}/{$str}");
        }, $files);

        return $files;
    }

    protected function buildPathToArchive($path): string
    {
        $fileName = basename($path);
        $pathToArchive = base_path()."storage/backups/{$fileName}.zip";

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
