<?php


namespace App\Singletons;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerSingleton
{
    protected static $instance;
    protected $log;

    private function __construct()
    {
        $this->createLogFile();
    }

    protected function createLogFile()
    {
        $pathToLog = base_path("storage/logs/".date("d.m.Y").".log");

        if(file_exists($pathToLog)) {
            unlink($pathToLog);
        }

        $this->log = new Logger('log');
        $this->log->pushHandler(new StreamHandler($pathToLog, Logger::DEBUG));
    }

    public static function getInstance(): self
    {
        if(self::$instance) {
            return self::$instance;
        }

        self::$instance = new self();

        return self::$instance;
    }

    public function debug($message): void
    {
        $this->log->debug($message);
    }

    public function error($message): void
    {
        $this->log->error($message);
    }

    public function exitWithError($message)
    {
        $this->error($message);
        $this->sendReport("Ошибка при создании бэкапа");
        exit();
    }

    public function sendReport(string $title)
    {
        return ;

        try {

        } catch (\Exception $e) {
            $this->log->error('Ошибка при отправке сообщения: '. $e->getMessage());
        }
    }
}