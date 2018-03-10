<?php

use Iassasin\Sinair\SampleApp\MyApplication as SinairApp;

define('ENV', 'WEB');

require_once __DIR__ . '/../vendor/autoload.php';

set_time_limit(1);
ini_set('display_errors', true);
ini_set('log_errors', true);
error_reporting(E_ALL);
$time = microtime(true);
$saw = new \Maestroprog\Saw\SawWeb(__DIR__ . '/../config/config.php');
$saw->app(SinairApp::class)->run();
var_dump((microtime(true) - $time) * 1000, 'ms');
