<?php

use Esockets\base\Configurator;
use Esockets\protocol\EasyDataGram;
use Esockets\socket\SocketFactory;
use Iassasin\Sinair\SampleApp\LoggingProtocol;

return [
    Configurator::CONNECTION_TYPE => Configurator::CONNECTION_TYPE_SOCKET,
    Configurator::CONNECTION_CONFIG => [
        SocketFactory::SOCKET_DOMAIN => AF_INET,
        SocketFactory::SOCKET_PROTOCOL => SOL_UDP,
        SocketFactory::WAIT_INTERVAL => 10,
    ],
    Configurator::PROTOCOL_CLASS => LoggingProtocol::withRealProtocolClass(EasyDataGram::class),
];
