<?php


namespace App\Interfaces;


interface CommandInterface
{
    /**
     * Execute command
     */
    public function execute(): void;
}