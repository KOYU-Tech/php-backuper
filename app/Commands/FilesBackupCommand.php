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
            $pathToArchive = $this->createArchive($file['entry'], $file['excluded']);

            //upload archive
            $folderToUpload = date("d.m.Y");
            $this->cloudStorage->upload($pathToArchive, $folderToUpload);

            //delete the archive with the dump from the server
            unlink($pathToArchive);
        }

        $this->logger->sendReport("Backup files created");
    }

    protected function createArchive(string $pathToFile, $excluded=[])
    {
        $pathToArchive = base_path("storage/backups/". basename($pathToFile) .".tar.gz");

        $excludeString = '';
        foreach ($excluded as $excludedItem) {
            $excludeString .= "--exclude \"{$excludedItem}\" ";
        }

        //TODO remove path in archive
        $command = "tar czf {$pathToArchive} {$excludeString} {$pathToFile} --warning=no-file-changed";

        exec($command, $output, $return_var);

        if($return_var) {
            $this->logger->exitWithError("Failed to create archive {$pathToFile}. Command: \"{$command}\". Error code: \"{$return_var}\"");
        }

        $this->logger->debug("Archive of {$pathToFile} was created");

        return $pathToArchive;
    }
}

