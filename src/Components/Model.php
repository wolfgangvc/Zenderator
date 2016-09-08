<?php

namespace Zenderator\Components;

use Thru\Inflection\Inflect;
use Zend\Db\Adapter\Adapter as DbAdaptor;

class Model extends Entity
{
    /** @var DbAdaptor */
    protected $dbAdaptor;

    protected $namespace;
    /** @var string */
    protected $database;
    /** @var string */
    protected $table;
    /** @var Column[] */
    protected $columns;
    protected $constraints;
    protected $primaryKeys;
    protected $autoIncrements;

    /**
     * @return mixed
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param mixed $namespace
     * @return Model
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function setAdaptor(DbAdaptor $dbAdaptor)
    {
        $this->dbAdaptor = $dbAdaptor;
        return $this;
    }

    /**
     * @return array
     */
    public function getConstraints()
    {
        return $this->constraints;
    }

    /**
     * @param array $constraints
     *
     * @return Model
     */
    public function setConstraints(array $constraints)
    {
        $this->constraints = $constraints;
        return $this;
    }

    /**
     * @param \Zend\Db\Metadata\Object\ConstraintObject[] $zendConstraints
     */
    public function computeConstraints(array $zendConstraints)
    {
        foreach ($zendConstraints as $zendConstraint) {
            if ($zendConstraint->getType() == "FOREIGN KEY") {
                $columnAffected = $zendConstraint->getColumns()[0];
            }
            if ($zendConstraint->getType() == "PRIMARY KEY") {
                $this->primaryKeys = $zendConstraint->getColumns();
            }
        }
    }

    public function computeAutoIncrementColumns()
    {
        $sql = "SHOW columns FROM `{$this->getTable()}` WHERE extra LIKE '%auto_increment%'";
        $query = $this->getAdaptor()->query($sql);
        $columns = [];

        foreach ($query->execute() as $aiColumn) {
            $columns[] = $aiColumn['Field'];
        }
        return $columns;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $table
     *
     * @return Model
     */
    public function setTable(string $table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * @return DbAdaptor
     */
    public function getAdaptor()
    {
        return $this->dbAdaptor;
    }

    /**
     * @param \Zend\Db\Metadata\Object\ColumnObject[] $columns
     * @return $this
     */
    public function computeColumns(array $columns)
    {
        foreach ($columns as $column) {
            $typeFragments = explode(" ", $column->getDataType());
            $oColumn = Column::Factory()
                ->setField($column->getName())
                ->setDbType(reset($typeFragments))
                ->setPermittedValues($column->getErrata('permitted_values'))
                ->setMaxDecimalPlaces($column->getNumericScale())
                ->setDefaultValue($column->getColumnDefault());

            /**
             * Calculate Max Length for field.
             */
            if (in_array($column->getDataType(), ['int', 'bigint', 'tinyint'])) {
                $oColumn->setMaxLength($column->getNumericPrecision());
            } else {
                $oColumn->setMaxLength($column->getCharacterMaximumLength());
            }

            switch ($column->getDataType()) {
                case 'bigint':
                    $oColumn->setMaxFieldLength(9223372036854775807);
                    break;
                case 'int':
                    $oColumn->setMaxFieldLength(2147483647);
                    break;
                case 'mediumint':
                    $oColumn->setMaxFieldLength(8388607);
                    break;
                case 'smallint':
                    $oColumn->setMaxFieldLength(32767);
                    break;
                case 'tinyint':
                    $oColumn->setMaxFieldLength(127);
                    break;
            }

            $this->columns[$oColumn->getPropertyName()] = $oColumn;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return
            $this->transSnake2Studly->transform($this->getDatabase()) .
            $this->transStudly2Studly->transform($this->getTable());
    }

    /**
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param string $database
     *
     * @return Model
     */
    public function setDatabase(string $database)
    {
        $this->database = $database;
        return $this;
    }

    public function getRenderDataset()
    {
        return [
            'namespace' => $this->getNamespace(),
            'app_name' => APP_NAME,
            'app_container' => APP_CORE_NAME,
            'class_name' => $this->getClassName(),
            'variable_name' => $this->transStudly2Camel->transform($this->getClassName()),
            'name' => $this->getClassName(),
            'object_name_plural' => Inflect::pluralize($this->getClassName()),
            'object_name_singular' => Inflect::singularize($this->getClassName()),
            'controller_route' => $this->transCamel2Snake->transform(Inflect::pluralize($this->getClassName())),
            'namespace_model' => "{$this->getNamespace()}\\Models\\{$this->getClassName()}Model",
            'columns' => $modelData['columns'],
            'related_objects' => $modelData['related_objects'],
            'remote_constraints' => isset($modelData['remote_constraints']) ? $this->makeConstraintArray($modelData['remote_constraints']) : false,
            'remote_constraints_tables' => isset($modelData['remote_constraints']) ? $this->makeConstraintTableList($modelData['remote_constraints']) : false,
            'database' => $modelData['database'],
            'table' => $modelData['table'],
            'primary_keys' => $modelData['primary_keys'],
            'primary_parameters' => $modelData['primary_parameters'],
            'autoincrement_parameters' => $modelData['autoincrement_parameters']
        ]
    }
}
