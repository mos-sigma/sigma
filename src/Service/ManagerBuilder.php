<?php

namespace Ni\Elastic\Service;

use Elasticsearch\Client as Elasticsearch;
use Ni\Elastic\Index\Manager;
use Ni\Elastic\Response\ResponseFactory;
use Ni\Elastic\Index\IndexBase;
use Ni\Elastic\Index\Index;
use Ni\Elastic\Index\IndexHandler;

class ManagerBuilder
{
    /**
     * Elasticsearch Client
     *
     * @var Elasticsearch
     */
    private $elasticsearch;

    public function __construct(Elasticsearch $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    public function build(): Manager
    {
        $manager = new Manager($this->elasticsearch, new IndexHandler);

        return $manager;
    }
}