<?php

return [
    'saw' => [
        'debug' => true,
    ],
    'daemon' => [
        'controller_path' => __DIR__ . '/../vendor/maestroprog/saw/bin/controller.php',
        'controller_pid' => __DIR__ . '/../cache/controller.pid',
        'worker_path' => __DIR__ . '/../vendor/maestroprog/saw/bin/worker.php',
        'listen_address' => new \Esockets\socket\Ipv4Address('127.0.0.1', 59092),
        'controller_address' => new \Esockets\socket\Ipv4Address('127.0.0.1', 59092),
    ],
    'controller' => [
        'worker_multiplier' => 1,
        'worker_max_count' => 4,
    ],
    'application' => [],
    'factory' => require __DIR__ . '/factory.php',
    'sockets' => require __DIR__ . '/esockets_debug.php',
    'multiThreading' => [
//        'disabled' => true,
    ]
];