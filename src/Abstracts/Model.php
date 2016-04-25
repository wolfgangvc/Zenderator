<?php
namespace Zenderator\Abstracts;

use Camel\CaseTransformer;
use Camel\Format;

abstract class Model
{
    protected $_primary_keys;
    
    public function __construct(array $data = [])
    {
        if ($data) {
            $this->exchangeArray($data);
        }
    }

    /**
     * @return \Interop\Container\ContainerInterface
     */
    public function getDIContainer()
    {
        return App::Container();
    }

    /**
     * @param array $data
     *
     * @return AbstractModel $this
     */
    public function exchangeArray(array $data)
    {
        $transformer = new CaseTransformer(new Format\CamelCase(), new Format\StudlyCaps());
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($transformer->transform($key));
            if (method_exists($this, $method)) {
                if (is_numeric($value)) {
                    $value = doubleval($value);
                }
                $this->$method($value);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function __toArray()
    {
        $array = [];

        $transformer = new CaseTransformer(new Format\StudlyCaps(), new Format\StudlyCaps());
        foreach (get_class_methods($this) as $key => $value) {
            if (0 === strpos($value, 'get') && !in_array($value, $this->getProtectedMethods())) {
                $currentValue = $this->$value();
                $array[$transformer->transform(substr($value, 3))] = $currentValue;
            }
        }

        return array_merge($array);
    }

    /**
     * Return primary key values in an associative array.
     *
     * @return array
     */
    public function getPrimaryKeys()
    {
        $primaryKeyValues = [];
        foreach ($this->_primary_keys as $primary_key) {
            $getFunction = "get{$primary_key}";
            $primaryKeyValues[$primary_key] = $this->$getFunction();
        }
        return $primaryKeyValues;
    }

    /**
     * Returns true if the primary key isn't null.
     *
     * @return bool
     */
    public function hasPrimaryKey()
    {
        $notNull = false;
        foreach ($this->getPrimaryKeys() as $primaryKey) {
            if ($primaryKey != null) {
                $notNull = true;
            }
        }
        return $notNull;
    }

    protected function getProtectedMethods()
    {
        return ['getPrimaryKeys', 'getProtectedMethods', 'getDIContainer'];
    }
}
