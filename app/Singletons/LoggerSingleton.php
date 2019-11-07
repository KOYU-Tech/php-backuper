<?php


namespace App\Singletons;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPMailer\PHPMailer\PHPMailer;

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
        $pathToLog = base_path()."storage/logs/".date("d.m.Y").".log";

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
        $this->sendEmailWithLog("Ошибка при создании бэкапа");
        exit($message);
    }

    public function sendEmailWithLog($title)
    {
        return ;
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
}