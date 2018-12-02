<?php
namespace App\Command;

use App\Command\BaseCommand;

class GitlabBackupCommand extends BaseCommand {
    
    public $nameCommand = "gitlab_backup";
    protected $requiredEnvVariables = ["MYSQL_USER", "MYSQL_PASSWORD", "EMAIL",  "EMAIL_COPY", "SMTP_HOST", "SMTP_USERNAME", "SMTP_PASSWORD", "SMTP_SECURE", "SMTP_PORT", "EMAIL_FROM", "SERVER_NAME", "WEBDAV_TOKEN", "WEBDAV_MININMUM_FREE_SPACE_GB", "GITLAB_BACKUP_PATH"];
    protected $baseFolderForEnv = 'FOLDER_FOR_GITLAB';
    protected $pathForBackup;
    
    public function __construct()
    {   
        parent::__construct();
    }
    
    public function run() 
    {
        //сохдаем папку для бэкапа
        $pathToUpload = $this->createFolderForUpload($this->baseFolderForCommandOnYandexDisk, $this->date);
        //создаем бэкап
        $gitlabArchive = $this->createGitlabBackup();

        $pathForGitlabBackup = realpath($this->GITLAB_BACKUP_PATH.DIRECTORY_SEPARATOR.$gitlabArchive);

        //определяем размер архива, чтобы освоболить под него место на яндекс диске
        $fileSize = filesize($pathForGitlabBackup);
        //определяем свобоное место на диске
        $this->checkFreeSpaceOnYandexDisk($fileSize, $this->WEBDAV_MININMUM_FREE_SPACE_GB, $this->baseFolderForCommandOnYandexDisk);
        //загружаем архив дампа на яндекс диск
        $this->uploadToYandexDisk($pathForGitlabBackup, $pathToUpload);

        $this->sendEmailWithLog("Бэкап Gitlab создан");

    }

    protected function createGitlabBackup()
    {
        $filesDirectoryBeforeNewBackup = array_diff(scandir($this->GITLAB_BACKUP_PATH), array('..', '.'));

        exec("gitlab-rake gitlab:backup:create", $output, $return_var);

        if($return_var) {
            $this->exitWithError("Не удалось создать бэкап. Код ошибки {$return_var}");
        }

        $filesDirectoryAfterNewBackup = array_diff(scandir($this->GITLAB_BACKUP_PATH), array('..', '.'));

        $newBackupFileNameArr = array_diff($filesDirectoryAfterNewBackup, $filesDirectoryBeforeNewBackup);

        $newBackupFileName = array_pop($newBackupFileNameArr);

        $this->log->debug("Успешно создан бэкап gitlab");

        return $newBackupFileName;
    }
}
