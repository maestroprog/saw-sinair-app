<?php

use Iassasin\Sinair\SampleApp\MyApplication as SinairApp;
use Maestroprog\Saw\Saw;

define('ENV', 'WEB');

require_once __DIR__ . '/../vendor/autoload.php';

Saw::instance()
    ->init(__DIR__ . '/../config.php')
    ->instanceApp(SinairApp::class)
    ->run();
