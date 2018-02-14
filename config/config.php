<?php

use Maestroprog\Saw\Memory\SharedMemoryInterface;
use Maestroprog\Saw\Thread\MultiThreadingProvider;

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
                ],
            ],
        ]
    ]
);
