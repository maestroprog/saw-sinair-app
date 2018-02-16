<?php

namespace Iassasin\Sinair\SampleApp\di;

use Iassasin\Fidb\Connection\ConnectionMysql;
use Iassasin\Sinair\SampleApp\MCGLOnline;
use Maestroprog\Container\AbstractBasicContainer;

class SinairContainer extends AbstractBasicContainer
{
    public function getConnection(): ConnectionMysql
    {
        return new ConnectionMysql(
            $this->get('Db_host'),
            $this->get('Db_name'),
            $this->get('Db_user'),
            $this->get('Db_pass')
        );
    }

    public function getOnline(): MCGLOnline
    {
        return new MCGLOnline($this->get(ConnectionMysql::class));
    }
}
