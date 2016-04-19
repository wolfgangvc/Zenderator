<?php
namespace Zenderator\Abstracts;

abstract class Service
{
    public function __construct(\Slim\Container $container)
    {
        $this->container = $container;
    }
    
    abstract function getNewModelInstance();

    abstract function getTermPlural() : string;
    abstract function getTermSingular() : string;
}
