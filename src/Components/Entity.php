<?php

namespace Zenderator\Components;

use Camel\CaseTransformer;
use Camel\Format;

class Entity
{
    /** @var CaseTransformer */
    protected $transSnake2Studly;
    /** @var CaseTransformer */
    protected $transStudly2Camel;
    /** @var CaseTransformer */
    protected $transStudly2Studly;
    /** @var CaseTransformer */
    protected $transCamel2Camel;
    /** @var CaseTransformer */
    protected $transCamel2Studly;
    /** @var CaseTransformer */
    protected $transSnake2Camel;
    /** @var CaseTransformer */
    protected $transSnake2Spinal;
    /** @var CaseTransformer */
    protected $transCamel2Snake;

    /** @var CaseTransformer */
    protected $transField2Property;

    public function __construct()
    {
        $this->transSnake2Studly = new CaseTransformer(new Format\SnakeCase(), new Format\StudlyCaps());
        $this->transStudly2Camel = new CaseTransformer(new Format\StudlyCaps(), new Format\CamelCase());
        $this->transStudly2Studly = new CaseTransformer(new Format\StudlyCaps(), new Format\StudlyCaps());
        $this->transCamel2Camel = new CaseTransformer(new Format\CamelCase(), new Format\CamelCase());
        $this->transCamel2Studly = new CaseTransformer(new Format\CamelCase(), new Format\StudlyCaps());
        $this->transSnake2Camel = new CaseTransformer(new Format\SnakeCase(), new Format\CamelCase());
        $this->transSnake2Spinal = new CaseTransformer(new Format\SnakeCase(), new Format\SpinalCase());
        $this->transCamel2Snake = new CaseTransformer(new Format\CamelCase(), new Format\SnakeCase());

        $this->transField2Property = $this->transCamel2Camel;
    }

    /**
     * @return self
     */
    public static function Factory()
    {
        $class = get_called_class();
        return new $class;
    }
}
