<?php
namespace App\Commands;

class FilesBackupCommand extends AbstractCommand
{
    protected $cloudStorageFolderConfig = "files_dump.cloud_storage_folder";
    protected $files;

    public function __construct()
    {
        parent::__construct();

        $this->files = $this->config->get('files');
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

