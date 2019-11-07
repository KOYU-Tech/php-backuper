<?php


namespace App\Interfaces;


interface CloudBehaviourInterface
{
    public function upload(string $file, string $folder): void;

    public function delete(string $file): void;
}