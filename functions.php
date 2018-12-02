<?php

function base_path() {
   return realpath(__DIR__).DIRECTORY_SEPARATOR;
}

function base_folder() {
    return basename(__DIR__);
}
