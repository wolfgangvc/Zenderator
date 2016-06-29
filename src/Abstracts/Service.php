<?php
namespace Zenderator\Abstracts;

abstract class Service
{    
    abstract function getNewModelInstance();

    abstract function getTermPlural() : string;
    abstract function getTermSingular() : string;
}
