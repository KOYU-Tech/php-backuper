<?php
namespace App\Command;

use \Dotenv\Dotenv;
use \Yandex\Disk\DiskClient;
use \Monolog\Logger;
use \Monolog\Formatter\LineFormatter;
use \Monolog\Handler\StreamHandler;
use \PHPMailer\PHPMailer\PHPMailer;

abstract class BaseCommand {
    
    public $nameCommand;
    protected $log;
    protected $date;
    protected $requiredEnvVariables;
    protected $diskClient;
    protected $baseFolderForEnv;
    protected $baseFolderForCommandOnYandexDisk;
    

    public function __construct()
    {   
        $this->date = date("d.m.Y");
        $this->pathToLog = base_path()."storage".DIRECTORY_SEPARATOR."logs".DIRECTORY_SEPARATOR.$this->nameCommand.".log";
        $this->createLogFile($this->pathToLog);
        $this->getEnvVariables($this->requiredEnvVariables);
        $this->setEnvVariablesToParameters($this->requiredEnvVariables);
        $this->checkBaseFolderForCommandInStorage($this->nameCommand);
        $this->setBaseFolderForCommandOnYandexDisk($this->baseFolderForEnv);
        $this->connectToYandexDisk($this->WEBDAV_TOKEN);
        $this->createFolderForUpload('', $this->baseFolderForCommandOnYandexDisk);
    }

    protected function createLogFile($pathToLog)
    {
        if(file_exists($pathToLog)) {
            unlink($pathToLog);
        }

        $this->log = new Logger($this->nameCommand);
        $formatter = new LineFormatter(null, null, false, true);
        $debugHandler = new StreamHandler($pathToLog, Logger::DEBUG);
        $debugHandler->setFormatter($formatter);
        $this->log->pushHandler($debugHandler);
    }

    protected function getEnvVariables(array $requiredEnvVariables = []) 
    {
        $dotenv = new Dotenv(base_path());
        $dotenv->load();
        try {
            $dotenv->required($requiredEnvVariables)->notEmpty();
        } catch (\Exception $e) {
            $this->exitWithError($e->getMessage());
        }

    }
    
    protected function setEnvVariablesToParameters(array $requiredEnvVariables  = []) 
    {   
        foreach ($requiredEnvVariables as $variable) {
            $this->$variable = getenv($variable);
        }
    }  
    
    protected function connectToYandexDisk($token)
    {
        try {
            $this->diskClient = new DiskClient($token);
            $this->diskClient->setServiceScheme(DiskClient::HTTPS_SCHEME); 
            $this->log->debug("Подключение к Yandex.Disk установлено");
        } catch (\Exception $e) {
            $this->exitWithError("Ошибка подключения к Yandex.Disk: ".$e->getMessage());
        }
    }
    
    protected function setBaseFolderForCommandOnYandexDisk($envVariableName) 
    {   
        if(!empty(getenv($envVariableName))) {
            $this->baseFolderForCommandOnYandexDisk = getenv($envVariableName);
        } else {
            $this->exitWithError("Не указана .env переменная - $envVariableName");
        }
    }
   
    protected function checkBaseFolderForCommandInStorage($folder) 
    {
        $path = base_path()."storage".DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR;

        if(!is_dir($path)) {
            mkdir($path);
        } else {
            $files = glob($path.'*');
            foreach($files as $file){
                if(is_file($file))
                    unlink($file);
            }
        }
        
        $this->pathForBackup = $path;
    }

    protected function createFolderForUpload($baseFolder, $newFolder)
    {
        $newFolderIsExsits = false;
        $pathToUpload = $baseFolder.DIRECTORY_SEPARATOR.$newFolder;

        try {
            $dirContent = $this->diskClient->directoryContents($baseFolder.DIRECTORY_SEPARATOR);
        } catch (\Exception $e) {
            $this->exitWithError("Ошибка при получении списка файлов в директории($baseFolder) на Yandex.Disk ".$e->getMessage());
        }

        foreach ($dirContent as $dirItem) {
            if ($dirItem['resourceType'] === 'dir' && $dirItem['displayName'] === $newFolder) {
                $newFolderIsExsits = true;
            }
        }

        if($newFolderIsExsits === false) {
            try {
                $this->diskClient->createDirectory($pathToUpload);
                $this->log->debug("Успешно создана папка($pathToUpload) на Yandex.Disk");
            } catch (\Exception $e) {
                $this->exitWithError("Ошибка при создании папки на Yandex.Disk ".$e->getMessage());
            }
        }

        return $pathToUpload;
    }
    
    //to do доделать удаление
    protected function checkFreeSpaceOnYandexDisk($fileSize, $limitForDelete, $baseFolderOnDisk)
    {
        try {
            $diskSpace = $this->diskClient->diskSpaceInfo();
        } catch (\Exception $e) {
            $this->exitWithError("Ошибка при получении информации о свободном месте на Yandex.Disk ".$e->getMessage());
        }
        
//        $diskFreeSpace = $diskSpace['availableBytes'] - $diskSpace['usedBytes'];
        $diskFreeSpace = $diskSpace['availableBytes'];
        $this->log->debug("Проверка свободного места Yandex.Disk. Свободно: $diskFreeSpace bytes");

        if($diskFreeSpace < $fileSize) {
           if($fileSize >= ($limitForDelete * pow(1024,3))){
               $this->exitWithError("Превышен лимит($limitForDelete gb) на удаление с диска");
           }
           $oldestFolder = $this->getPathToOldestBackup($baseFolderOnDisk);
           $this->deleteOnYandexDisk($oldestFolder);
           $this->checkFreeSpaceOnYandexDisk($fileSize, $limitForDelete, $baseFolderOnDisk);
        }        
        
    }
    
    protected function getPathToOldestBackup($folder)
    {   
        try {
            $dirContent = $this->diskClient->directoryContents($folder.DIRECTORY_SEPARATOR);
        } catch (\Exception $e) {
            $this->exitWithError("Ошибка при получении списка файлов в директории на Yandex.Disk ".$e->getMessage());
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
            $this->exitWithError("Не удалось получить самый старый бэкап");
        }
        
        return $oldestFolder;        
    }
    
    //удаляет самую старуню папку с бэкапом в указанной директории
    protected function deleteOnYandexDisk($path)
    {
        try {
            $this->diskClient->delete($path);
            $this->log->debug("Успешно удален файл($path) с Yandex.Disk");
        } catch (\Exception $e) {
            $this->exitWithError("Ошибка при удалении на Yandex.Disk ".$e->getMessage());
        }
        
    }
    
    //to do доделать удаление
    protected function uploadToYandexDisk($filepath, $pathToUpload) 
    {
        try {
            $this->diskClient->uploadFile(
                DIRECTORY_SEPARATOR.$pathToUpload.DIRECTORY_SEPARATOR,
                array(
                    'path' => $filepath,
                    'size' => filesize($filepath),
                    'name' => basename($filepath)
                )
            );
            $this->log->debug("Успешно загружен файл(".basename($filepath).") на Yandex.Disk");
        } catch (\Exception $e) {
            $this->exitWithError("Ошибка при загрузке для бэкапа на Yandex.Disk ".$e->getMessage());
        }        
            
    }

    protected function createArchive($source, $destination)
    {

        try {
            $zip = new \ZipArchive();
            if ($zip->open($destination, \ZIPARCHIVE::CREATE) === true) {
                $source = realpath($source);
                if (is_dir($source) === true) {
                    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::SELF_FIRST);
                    foreach ($files as $file) {
                        $file = realpath($file);
                        if (is_dir($file) === true) {
                            $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                        } else if (is_file($file) === true) {
                            $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                        }
                    }
                } else if (is_file($source) === true) {
                    $zip->addFile($source, $destination);
                }
            }
            $zip->close();
            $this->log->debug("Успешно создан архив $destination");
        } catch (\Exception $e) {
            $this->exitWithError("Ошибка при создании архива для файла $source: ".$e->getMessage());
        }
    }

    protected function exitWithError($message)
    {   
        $this->log->error($message);
        $this->sendEmailWithLog("Ошибка при создании бэкапа");
        exit($message);
    }
    
    protected function sendEmailWithLog($title)
    {   
        $mail = new PHPMailer(true);
        try {

            $mail->SMTPDebug = 2;
            $mail->isSMTP();
            $mail->Host = $this->SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = $this->SMTP_USERNAME;
            $mail->Password = $this->SMTP_PASSWORD;
            $mail->SMTPSecure = $this->SMTP_SECURE;
            $mail->Port = $this->SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            //Recipients
            $mail->setFrom($this->EMAIL_FROM, $this->SERVER_NAME);
            $mail->addAddress($this->EMAIL);

            $copy_to = array_map('trim', explode(',', $this->EMAIL_COPY));

            foreach ($copy_to as $email) {
                $mail->addAddress($email);
            }

            //Attachments
            $mail->addAttachment($this->pathToLog);

            //Content
            $mail->isHTML(true);
            $mail->Subject = $title;
            $mail->Body    = 'Отчет о выполнении команды в приложении.';
            $mail->AltBody = 'Это письмо создано автоматически.';

            $mail->send();
            
        } catch (\Exception $e) {
            $this->log->error('Ошибка при отправке сообщения: '. $e->getMessage());
        }
    }        
    
    abstract public function run(); 
    
}
