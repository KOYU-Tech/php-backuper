# PHP Backuper

## Install

```
$ git clone https://github.com/KOYU-Tech/php-backuper.git
$ composer install
$ cp .env.save .env
$ nano .env (edit parameters and credentials)
$ crontab -e (paste the command)
```

## Commands

### Create dump of DB

`php console databases_backup`

### Create files backup in the target directory

`php console ./target_directory/path/`

### To create backup of Gitlab projects in the target directory

`php console gitlab_backup`

Note: it uses `gitlab-rake gitlab:backup:create` and it requires root access