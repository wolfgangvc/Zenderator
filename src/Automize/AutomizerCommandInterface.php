<?php
namespace Zenderator\Automize;

interface AutomizerCommandInterface
{
    public function getCommandName() : string;

    public function action() : bool;
}
