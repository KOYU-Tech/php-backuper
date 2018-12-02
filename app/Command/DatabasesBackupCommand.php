<?php
namespace App\Command;

use App\Command\BaseCommand;

class DatabasesBackupCommand extends BaseCommand {
    
    public $nameCommand = "databases_backup";
    protected $requiredEnvVariables = ["MYSQL_USER", "MYSQL_PASSWORD", "EMAIL", "EMAIL_COPY", "SMTP_HOST", "SMTP_USERNAME", "SMTP_PASSWORD", "SMTP_SECURE", "SMTP_PORT", "EMAIL_FROM", "SERVER_NAME", "WEBDAV_TOKEN", "WEBDAV_MININMUM_FREE_SPACE_GB"];
    protected $baseFolderForEnv = 'WEBDAV_MYSQL_DUMP_FOLDER';
    protected $pathForBackup;
    
    public function __construct()
    {   
        parent::__construct();
    }
    
    public function run() 
    {
        //сохдаем папку для бэкапа
        $pathToUpload = $this->createFolderForUpload($this->baseFolderForCommandOnYandexDisk, $this->date);

        $listOfDatabases = $this->getListOfDatabases($this->MYSQL_USER, $this->MYSQL_PASSWORD);

        foreach ($listOfDatabases as $nameDatabase) {
            //создаем дамп бд
            $pathForNewDump = $this->createMySQLDump($this->MYSQL_USER, $this->MYSQL_PASSWORD, $nameDatabase, $this->pathForBackup, $this->date);
            //формируем путь к архиву
            $pathForNewArchive = basename($pathForNewDump).".zip";
            //создаем архив
            $this->createArchive($pathForNewDump, $pathForNewArchive);
            //удаляем дамп
            unlink($pathForNewDump);
            //определяем размер архива, чтобы освоболить под него место на яндекс диске
            $fileSize = filesize($pathForNewArchive);
            //определяем свобоное место на диске
            $this->checkFreeSpaceOnYandexDisk($fileSize, $this->WEBDAV_MININMUM_FREE_SPACE_GB, $this->baseFolderForCommandOnYandexDisk);
            //загружаем архив дампа на яндекс диск
            $this->uploadToYandexDisk($pathForNewArchive, $pathToUpload);
            //удаляем архив с дампом с сервера
            unlink($pathForNewArchive);
        }

        $this->sendEmailWithLog("Бэкап БД создан");

    }

    protected function getListOfDatabases($user, $password)
    {
        exec("mysql -u $user -p$password -e'show databases;'", $output);

        if(empty($output)) {
            $this->exitWithError("Не удалось получить список БД");
        }

        $listDatabses = array_diff($output, ['Database', 'information_schema', 'performance_schema', 'mysql', 'sys']);

        return $listDatabses;
    }

    protected function createMySQLDump($user, $password, $nameDatabase, $pathForBackup, $date)
    {
        $pathForNewDump = $pathForBackup.$nameDatabase."-".$date.".sql";

        exec("mysqldump -u $user -p$password $nameDatabase > ".$pathForNewDump, $output, $return_var);

        if($return_var) {
            $this->exitWithError("Не удалось создать дамп для БД $nameDatabase . Код ошибки $return_var");
        }

        $this->log->debug("Успешно создан дамп базы данных $nameDatabase");

        return $pathForNewDump;
    }

}
