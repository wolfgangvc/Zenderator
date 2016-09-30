<?php

namespace Zenderator\Components;

use Zenderator\Exception\DBTypeNotTranslatedException;

class Column extends Entity
{

    protected $field;
    protected $dbType;
    protected $phpType;
    protected $maxLength;
    protected $maxFieldLength;
    protected $maxDecimalPlaces;
    protected $permittedValues;
    protected $defaultValue;
    protected $isAutoIncrement = false;
    /** @var RelatedModel[] */
    protected $relatedObjects;
    /** @var RelatedModel[] */
    protected $remoteObjects;

    /**
     * @return bool
     */
    public function isAutoIncrement(): bool
    {
        return $this->isAutoIncrement;
    }

    /**
     * @param bool $isAutoIncrement
     *
     * @return Column
     */
    public function setIsAutoIncrement(bool $isAutoIncrement): Column
    {
        $this->isAutoIncrement = $isAutoIncrement;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPhpType()
    {
        return $this->phpType;
    }

    /**
     * @param mixed $phpType
     *
     * @return Column
     */
    public function setPhpType($phpType)
    {
        $this->phpType = $phpType;
        return $this;
    }

    public function getPropertyName()
    {
        return $this->transField2Property->transform($this->getField());
    }

    /**
     * @return mixed
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param mixed $field
     *
     * @return Column
     */
    public function setField($field)
    {
        $this->field = $field;
        return $this;
    }

    public function getPropertyFunction()
    {
        return $this->transCamel2Studly->transform($this->getField());
    }

    /**
     * @return mixed
     */
    public function getMaxDecimalPlaces()
    {
        return $this->maxDecimalPlaces;
    }

    /**
     * @param mixed $maxDecimalPlaces
     *
     * @return Column
     */
    public function setMaxDecimalPlaces($maxDecimalPlaces)
    {
        $this->maxDecimalPlaces = $maxDecimalPlaces;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param mixed $defaultValue
     *
     * @return Column
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @param mixed $maxLength
     *
     * @return Column
     */
    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMaxFieldLength()
    {
        return $this->maxFieldLength;
    }

    /**
     * @param mixed $maxFieldLength
     *
     * @return Column
     */
    public function setMaxFieldLength($maxFieldLength)
    {
        $this->maxFieldLength = $maxFieldLength;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDbType()
    {
        return $this->dbType;
    }

    /**
     * @param mixed $dbType
     *
     * @throws DBTypeNotTranslatedException
     *
     * @return Column
     */
    public function setDbType($dbType)
    {
        $this->dbType = $dbType;
        switch ($this->getDbType()) {
            case 'float':
            case 'decimal':
                $this->setPhpType('float');
                break;
            case 'bit':
            case 'int':
            case 'bigint':
            case 'tinyint':
                $this->setPhpType('int');
                break;
            case 'varchar':
            case 'smallblob':
            case 'blob':
            case 'longblob':
            case 'smalltext':
            case 'text':
            case 'longtext':
                $this->setPhpType('string');
                break;
            case 'enum':
                $this->setPhpType('string');
                break;
            case 'datetime':
                $this->setPhpType('string');
                break;
            default:
                throw new DBTypeNotTranslatedException("Type not translated: {$this->getDbType()}");
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPermittedValues()
    {
        return $this->permittedValues;
    }

    /**
     * @param mixed $permittedValues
     *
     * @return Column
     */
    public function setPermittedValues($permittedValues)
    {
        $this->permittedValues = $permittedValues;
        return $this;
    }

    /**
     * @param RelatedModel $relatedModel
     *
     * @return $this
     */
    public function addRelatedObject(RelatedModel $relatedModel)
    {
        $this->relatedObjects[] = $relatedModel;
        return $this;
    }

    /**
     * @param RelatedModel $relatedModel
     *
     * @return $this
     */
    public function addRemoteObject(RelatedModel $relatedModel)
    {
        $this->remoteObjects[] = $relatedModel;
        return $this;
    }

    public function hasRelatedObjects() : bool
    {
        return count($this->relatedObjects) > 0;
    }

    public function hasRemoteObjects() : bool
    {
        return count($this->remoteObjects) > 0;
    }

    /**
     * @return RelatedModel[]
     */
    public function getRelatedObjects()
    {
        return $this->relatedObjects;
    }

    /**
     * @return RelatedModel[]
     */
    public function getRemoteObjects()
    {
        return $this->remoteObjects;
    }
}
