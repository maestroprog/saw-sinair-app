<?php

use Esockets\Base\Configurator;
use Esockets\Protocol\EasyDataGram;
use Esockets\Socket\SocketFactory;
use Iassasin\Sinair\SampleApp\LoggingProtocol;

return [
    Configurator::CONNECTION_TYPE => Configurator::CONNECTION_TYPE_SOCKET,
    Configurator::CONNECTION_CONFIG => [
        SocketFactory::SOCKET_DOMAIN => AF_INET,
        SocketFactory::SOCKET_PROTOCOL => SOL_UDP,
//        SocketFactory::WAIT_INTERVAL => 1,
    ],
    Configurator::PROTOCOL_CLASS => LoggingProtocol::withRealProtocolClass(EasyDataGram::class),
];
