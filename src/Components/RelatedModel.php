<?php

namespace Zenderator\Components;

class RelatedModel extends Entity
{
    protected $schema;
    protected $localTable;
    protected $remoteTable;
    protected $localBoundSchema;
    protected $localBoundColumn;
    protected $remoteBoundSchema;
    protected $remoteBoundColumn;

    /**
     * @return mixed
     */
    public function getRemoteTable()
    {
        return $this->remoteTable;
    }

    /**
     * @param mixed $remoteTable
     * @return RelatedModel
     */
    public function setRemoteTable($remoteTable)
    {
        $this->remoteTable = $remoteTable;
        return $this;
    }

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

    public function getRemoteClass()
    {
        return
            $this->transCamel2Studly->transform($this->remoteBoundSchema) .
            $this->transCamel2Studly->transform($this->remoteTable);
    }

    public function getRemoteVariable()
    {
        return
            $this->transCamel2Camel->transform($this->remoteBoundSchema) .
            $this->transCamel2Studly->transform($this->remoteTable);
    }

    public function getLocalClass()
    {
        return
            $this->transCamel2Studly->transform($this->localBoundSchema) .
            $this->transCamel2Studly->transform($this->localTable);
    }

    public function getLocalVariable()
    {
        return
            $this->transCamel2Camel->transform($this->localBoundSchema) .
            $this->transCamel2Studly->transform($this->localTable);
    }

    public function getLocalBoundColumnGetter(){
        return "get" . $this->transCamel2Studly->transform($this->getLocalBoundColumn());
    }

    /**
     * @return mixed
     */
    public function getLocalBoundColumn()
    {
        return $this->localBoundColumn;
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

    public function getRemoteBoundColumnGetter(){
        return "get" . $this->transCamel2Studly->transform($this->getRemoteBoundColumn());
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
    public function getLocalTable()
    {
        return $this->localTable;
    }

    /**
     * @param mixed $localTable
     * @return RelatedModel
     */
    public function setLocalTable($localTable)
    {
        $this->localTable = $localTable;
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
