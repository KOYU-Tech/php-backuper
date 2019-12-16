<?php
namespace App;

use \App\Commands\DatabasesBackupCommand;
use \App\Commands\FilesBackupCommand;
use \App\Commands\GitlabBackupCommand;

class RunCommand {

    protected $commands = [
        'databases_backup' => DatabasesBackupCommand::class,
        'files_backup' => FilesBackupCommand::class,
        'gitlab_backup' => GitlabBackupCommand::class,
    ];
    
    public function __construct($command)
    {
        if(isset($this->commands[$command])) {
            $command = new $this->commands[$command];
            $command->execute();
        } else {
            echo "Command '{$command}' doesn't exits";
        }

    }
}
