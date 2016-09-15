<?php

namespace Zenderator\Components;

class RelatedModel extends Entity
{
    protected $schema;
    protected $table;

    protected $localBoundSchema;
    protected $localBoundColumn;
    protected $remoteBoundSchema;
    protected $remoteBoundColumn;

    /**
     * @return mixed
     */
    public function getLocalBoundSchema()
    {
        return $this->localBoundSchema;
    }

    /**
     * @param mixed $localBoundSchema
     * @return RelatedModel
     */
    public function setLocalBoundSchema($localBoundSchema)
    {
        $this->localBoundSchema = $localBoundSchema;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRemoteBoundSchema()
    {
        return $this->remoteBoundSchema;
    }

    /**
     * @param mixed $remoteBoundSchema
     * @return RelatedModel
     */
    public function setRemoteBoundSchema($remoteBoundSchema)
    {
        $this->remoteBoundSchema = $remoteBoundSchema;
        return $this;
    }

    public function getName()
    {
        return
            $this->transCamel2Studly->transform($this->getSchema()) .
            $this->transCamel2Studly->transform($this->table);
    }

    public function getRemoteClass()
    {
        return
            $this->transCamel2Studly->transform($this->remoteBoundSchema) .
            $this->transCamel2Studly->transform($this->table);
    }

    public function getRemoteVariable()
    {
        return
            $this->transCamel2Camel->transform($this->remoteBoundSchema) .
            $this->transCamel2Studly->transform($this->table);
    }
    public function getLocalClass()
    {
        return
            $this->transCamel2Studly->transform($this->localBoundSchema) .
            $this->transCamel2Studly->transform($this->table);
    }

    public function getLocalVariable()
    {
        return
            $this->transCamel2Camel->transform($this->localBoundSchema) .
            $this->transCamel2Studly->transform($this->table);
    }

    /**
     * @return mixed
     */
    public function getLocalBoundColumn()
    {
        return $this->localBoundColumn;
    }

    public function getLocalBoundColumnGetter(){
        return "get" . $this->transCamel2Studly->transform($this->getLocalBoundColumn());
    }

    /**
     * @param mixed $localBoundColumn
     * @return RelatedModel
     */
    public function setLocalBoundColumn($localBoundColumn)
    {
        $this->localBoundColumn = $localBoundColumn;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRemoteBoundColumn()
    {
        return $this->remoteBoundColumn;
    }

    /**
     * @param mixed $remoteBoundColumn
     * @return RelatedModel
     */
    public function setRemoteBoundColumn($remoteBoundColumn)
    {
        $this->remoteBoundColumn = $remoteBoundColumn;
        return $this;
    }


    /**
     * @return mixed
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @param mixed $schema
     * @return RelatedModel
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param mixed $table
     * @return RelatedModel
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @param $localSchema
     * @param $localColumn
     * @param $remoteSchema
     * @param $remoteColumn
     * @return RelatedModel
     */
    public function setBindings(
        string $localSchema,
        string $localColumn,
        string $remoteSchema,
        string $remoteColumn
    ){
        return $this
            ->setLocalBoundSchema($localSchema)
            ->setLocalBoundColumn($localColumn)
            ->setRemoteBoundSchema($remoteSchema)
            ->setRemoteBoundColumn($remoteColumn);
    }
}
