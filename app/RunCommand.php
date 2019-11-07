<?php
namespace App;

use \Dotenv\Dotenv;
use \App\Commands\DatabasesBackupCommand;
use \App\Commands\FilesBackupCommand;
use \App\Commands\GitlabBackupCommand;

class RunCommand {
    protected $requiredEnvVariables =
        ["FILES_FOLDER", "EXCLUDE_FILES", "EMAIL", "EMAIL_COPY", "SMTP_HOST", "SMTP_USERNAME", "SMTP_PASSWORD", "SMTP_SECURE", "SMTP_PORT", "EMAIL_FROM", "SERVER_NAME", "WEBDAV_TOKEN", "WEBDAV_MININMUM_FREE_SPACE_GB"];

    protected $commands = [
        'databases_backup' => DatabasesBackupCommand::class,
        'files_backup' => FilesBackupCommand::class,
        'gitlab_backup' => GitlabBackupCommand::class,
    ];
    
    public function __construct($command)
    {
        $this->getEnvVariables();

        if(isset($this->commands[$command])) {
            $command = new $this->commands[$command];
            $command->execute();
        } else {
            echo "Command '{$command}' doesn't exits";
        }

    }

    protected function getEnvVariables(array $requiredEnvVariables = [])
    {
        $dotenv = new Dotenv(base_path());
        $dotenv->load();
//        try {
//            $dotenv->required($requiredEnvVariables)->notEmpty();
//        } catch (\Exception $e) {
//            $this->exitWithError($e->getMessage());
//        }

    }
}
