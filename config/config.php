<?php

use Iassasin\Fidb\Connection\ConnectionMysql;
use Iassasin\Sinair\SampleApp\di\ParamsContainer;
use Iassasin\Sinair\SampleApp\di\SinairContainer;
use Iassasin\Sinair\SampleApp\MCGLOnline;
use Iassasin\Sinair\SampleApp\MyApplication;
use Maestroprog\Saw\Application\Context\ContextPool;
use Maestroprog\Saw\Memory\SharedMemoryInterface;
use Maestroprog\Saw\Thread\MultiThreadingProvider;

define('APP_CONFIG_DIR', __DIR__ . '/');
$configs = [
    'params.php',
    'saw.php',
];
foreach ($configs as $configFile) {
    $configFile = APP_CONFIG_DIR . $configFile;
    if (!file_exists($configFile)) {
        copy($configFile . 'dist', $configFile);
    }
}

return array_merge_recursive(
    require __DIR__ . '/saw.php',
    [
        'application' => [
            MyApplication::ID => [
                'class' => MyApplication::class,
                'arguments' => [
                    'id' => '@appId',
                    'multiThreadingProvider' => MultiThreadingProvider::class,
                    'applicationMemory' => SharedMemoryInterface::class,
                    'contextPool' => ContextPool::class,
                    'mcglOnline' => MCGLOnline::class,
                    'db' => ConnectionMysql::class,
                ],
            ],
        ],
        'di' => [
            SinairContainer::class,
            new ParamsContainer(require APP_CONFIG_DIR . 'params.php'),
        ],
    ]
);
