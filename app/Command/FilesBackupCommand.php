<?php
namespace App\Command;

use App\Command\BaseCommand;

class FilesBackupCommand extends BaseCommand {
    
    public $nameCommand = "files_backup";
    protected $requiredEnvVariables = ["FILES_FOLDER", "EXCLUDE_FILES", "EMAIL", "EMAIL_COPY", "SMTP_HOST", "SMTP_USERNAME", "SMTP_PASSWORD", "SMTP_SECURE", "SMTP_PORT", "EMAIL_FROM", "SERVER_NAME", "WEBDAV_TOKEN", "WEBDAV_MININMUM_FREE_SPACE_GB"];
    protected $baseFolderForEnv = 'WEBDAV_FILES_FOLDER';
    protected $pathForBackup;
    
    public function __construct()
    {   
        parent::__construct();
    }
    
    public function run() 
    {
        //сохдаем папку для бэкапа
        $pathToUpload = $this->createFolderForUpload($this->baseFolderForCommandOnYandexDisk, $this->date);
        $files = $this->getFilesForBackup($this->FILES_FOLDER, $this->EXCLUDE_FILES);

        foreach ($files as $file) {
            $pathForFile = $this->FILES_FOLDER.DIRECTORY_SEPARATOR.$file;
            $pathForNewArchive = $this->pathForBackup.$file.".zip";
            //создаем архив
            $this->createArchive($pathForFile, $pathForNewArchive);
            //определяем размер архива, чтобы освоболить под него место на яндекс диске
            $fileSize = filesize($pathForNewArchive);
            //определяем свобоное место на диске
            $this->checkFreeSpaceOnYandexDisk($fileSize, $this->WEBDAV_MININMUM_FREE_SPACE_GB, $this->baseFolderForCommandOnYandexDisk);
            //загружаем архив дампа на яндекс диск
            $this->uploadToYandexDisk($pathForNewArchive, $pathToUpload);
            //удаляем архив с дампом с сервера
            unlink($pathForNewArchive);
        }

        $this->sendEmailWithLog("Бэкап файлов создан");
    }

    protected function getFilesForBackup($filesFolder, $excludedFilesString)
    {
        $excludedFiles = explode(",", $excludedFilesString);
        $excludedFiles = array_map('trim', $excludedFiles);
        array_push($excludedFiles, '..');
        array_push($excludedFiles, '.');
        array_push($excludedFiles, base_folder());

        $files = array_diff(scandir($filesFolder), $excludedFiles);

        return $files;
    }
}
