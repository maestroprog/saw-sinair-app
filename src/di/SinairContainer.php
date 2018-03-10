<?php

namespace Iassasin\Sinair\SampleApp\di;

use Iassasin\Fidb\Connection\ConnectionMysql;
use Iassasin\Sinair\SampleApp\MCGLOnline;
use Maestroprog\Container\HasContainerLinkInterface;
use Maestroprog\Container\WithContainerLinkTrait;

class SinairContainer implements HasContainerLinkInterface
{
    use WithContainerLinkTrait;

    public function getConnection(): ConnectionMysql
    {
        return new ConnectionMysql(
            $this->container->get('Db_host'),
            $this->container->get('Db_name'),
            $this->container->get('Db_user'),
            $this->container->get('Db_pass')
        );
    }

    public function getOnline(): MCGLOnline
    {
        return new MCGLOnline($this->container->get(ConnectionMysql::class));
    }
}
