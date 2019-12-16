<?php

function base_path($relative_path='') {
   return realpath(__DIR__).DIRECTORY_SEPARATOR.$relative_path;
}

function base_folder() {
    return basename(__DIR__);
}
