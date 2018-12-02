<?php
namespace App;

class RegisterCommands {
    protected $commnands = [
        'databases_backup' => '\App\Command\DatabasesBackupCommand',
        'files_backup' => '\App\Command\FilesBackupCommand',
        'gitlab_backup' => '\App\Command\GitlabBackupCommand',
    ];
    
    public function __construct($commnad){
        if(isset($this->commnands[$commnad])) {
            $class = $this->commnands[$commnad];
            
            $object = new $class;
            $object->run();
        } else {
            echo "Команды $commnad не существует";
        }
    }
}
