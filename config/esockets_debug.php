<?php

use Esockets\base\Configurator;
use Esockets\socket\SocketFactory;

return [
    Configurator::CONNECTION_TYPE => Configurator::CONNECTION_TYPE_SOCKET,
    Configurator::CONNECTION_CONFIG => [
        SocketFactory::SOCKET_DOMAIN => AF_INET,
        SocketFactory::SOCKET_PROTOCOL => SOL_UDP,
        SocketFactory::WAIT_INTERVAL => 10,
    ],
    Configurator::PROTOCOL_CLASS => LoggingProtocol::withRealProtocolClass(\Esockets\protocol\EasyDataGram::class),
];
