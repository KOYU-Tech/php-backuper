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

        $baseFolder = $this->config->get("files_dump.cloud_storage_folder");
        $this->cloudStorage = (new CloudBehaviourFactory())->create($baseFolder);
    }

    public function execute(): void
    {
        foreach ($this->files as $file) {
            //create archive
            $pathToArchive = $this->buildPathToArchive($file['entry']);
            $this->createArchive($pathToArchive, $file['entry'], $file['excluded']);

            //upload archive
            $folderToUpload = date("d.m.Y");
            $this->cloudStorage->upload($pathToArchive, $folderToUpload);

            //delete the archive with the dump from the server
            unlink($pathToArchive);
        }

        $this->logger->sendReport("Backup files created");
    }

    protected function buildPathToArchive($path): string
    {
        $fileName = basename($path);
        $pathToArchive = base_path("storage/backups/{$fileName}.tar.gz");

        return $pathToArchive;
    }

    protected function createArchive(string $pathToArchive, string $pathToFile, $excluded=[])
    {

        $excludeString = '';
        foreach ($excluded as $excludedItem) {
            $excludeString .= "--exclude \"{$excludedItem}\" ";
        }

        $command = "tar czf {$pathToArchive} {$excludeString} {$pathToFile} --warning=no-file-changed";

        exec($command, $output, $return_var);

        if($return_var) {
            $this->logger->exitWithError("Failed to create archive {$pathToFile}. Error code {$return_var}");
        }

        $this->logger->debug("Archive of {$pathToFile} was created");
    }
}

