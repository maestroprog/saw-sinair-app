<?php

namespace Iassasin\Sinair\SampleApp\di;

use Maestroprog\Container\Argument;
use Maestroprog\Container\IterableContainerInterface;

class ParamsContainer implements IterableContainerInterface
{
    private $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function get($id)
    {
        return $this->params[$id];
    }

    public function has($id)
    {
        return isset($this->params[$id]);
    }

    public function list(): array
    {
        $list = [];

        foreach (array_keys($this->params) as $name) {
            $list[$name] = new Argument($name, 'string', []);
        }

        return $list;
    }
}
