<?php
namespace Zenderator\Abstracts;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Adapter\Exception\InvalidQueryException;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway as ZendTableGateway;
use Zenderator\Exceptions\TableGatewayException;

abstract class TableGateway extends ZendTableGateway
{

    public $container;

    protected $model;

    /**
     * AbstractTableGateway constructor.
     *
     * @param \Slim\Container $container
     * @param AdapterInterface $adapter
     */
    public function __construct(\Slim\Container $container, AdapterInterface $adapter)
    {
        $this->container = $container;

        $resultSetPrototype = new ResultSet(ResultSet::TYPE_ARRAYOBJECT, new $this->model);
        return parent::__construct($this->table, $adapter, null, $resultSetPrototype);
    }

    /**
     * @param Model $model
     *
     * @return array|\ArrayObject|null
     * @throws TableGatewayException
     */
    public function save(Model $model)
    {
        $pk = $model->getPrimaryKeys();

        $pkIsBlank = true;
        foreach ($pk as $key => $value) {
            if (!is_null($value)) {
                $pkIsBlank = false;
            }
        }

        try {
            /** @var Model $oldModel */
            $oldModel = $this->select($pk)->current();
            if ($pkIsBlank || !$oldModel) {
                $pk = $this->saveInsert($model);
                if (!is_array($pk)) {
                    $pk = ['id' => $pk];
                }
                return $this->getByPrimaryKey($pk);
            } else {
                $this->saveUpdate($model, $oldModel);
                return $this->getByPrimaryKey($pk);
            }
        } catch (InvalidQueryException $iqe) {
            throw new InvalidQueryException(
                "While trying to call " . get_class() . "->save(): ... " . $iqe->getMessage(),
                $iqe->getCode(),
                $iqe
            );

        }
    }

    /**
     * @param Model $model
     *
     * @return int|null
     */
    public function saveInsert(Model $model)
    {
        $data = $model->__toArray();
        $this->insert($data);

        if ($model->hasPrimaryKey()) {
            return $model->getPrimaryKeys();
        } else {
            return $this->getLastInsertValue();
        }
    }


    /**
     * @param Model $model
     * @param Model $oldModel
     *
     * @return int
     */
    public function saveUpdate(Model $model, Model $oldModel)
    {
        return $this->update(
            $model->__toArray(),
            $model->getPrimaryKeys(),
            $oldModel->__toArray()
        );
    }

    /**
     * @param array $data
     *
     * @param null $id
     *
     * @return int
     */
    public function insert($data, &$id = null)
    {
        return parent::insert($data);
    }

    /**
     * @param array $data
     * @param null $where
     * @param array|Model $oldData
     *
     * @return int
     */
    public function update($data, $where = null, $oldData = [])
    {
        return parent::update($data, $where);
    }

    /**
     * This method is only supposed to be used by getListAction
     *
     * @param null $limit Number to limit to
     * @param null $order Column to order on
     * @param string $direction Direction to order on (SELECT::ORDER_ASCENDING|SELECT::ORDER_DESCENDING)
     *
     * @return array [ResultSet,int] Returns an array of resultSet,total_found_rows
     */
    public function fetchAll($limit = null, $order = null, $direction = Select::ORDER_ASCENDING)
    {
        /** @var Select $select */
        $select = $this->getSql()->select();

        if ($limit !== null && is_numeric($limit)) {
            $select->limit(intval($limit));
        }

        if ($order !== null){
            $select->order("{$order} {$direction}");
        }

        $resultSet = $this->selectWith($select);

        $quantifierSelect = $select
            ->reset(Select::LIMIT)
            ->reset(Select::COLUMNS)
            ->reset(Select::OFFSET)
            ->reset(Select::ORDER)
            ->reset(Select::COMBINE)
            ->columns(['total' => new Expression('COUNT(*)')]);

        /* execute the select and extract the total */
        $row   = $this->getSql()
            ->prepareStatementForSqlObject($quantifierSelect)
            ->execute()
            ->current();
        $total = (int)$row['total'];

        return [$resultSet, $total];
    }

    /**
     * @return array|\ArrayObject|null
     * @throws TableGatewayException
     */
    public function fetchRandom()
    {
        $resultSet = $this->select(function (Select $select) {
            $select->order(new Expression('RAND()'))->limit(1);
        });

        if (0 == count($resultSet)) {
            throw new TableGatewayException("No data found in table!");
        }

        return $resultSet->current();
    }

    /**
     * @param array|Select $where
     * @param array|string $order
     * @param int $offset
     *
     * @return array|\ArrayObject|null|Model
     * @throws TableGatewayException
     */
    public function fetchRow($where = null, $order = null, $offset = null)
    {
        if ($where instanceof Select) {
            $resultSet = $this->selectWith($where);
        } else {
            $resultSet = $this->select(function (Select $select)
 use ($where, $order, $offset) {
                if (!is_null($where)) {
                    $select->where($where);
                }
                if (!is_null($order)) {
                    $select->order($order);
                }
                if (!is_null($offset)) {
                    $select->offset($offset);
                }
                $select->limit(1);
            });
        }

        return (count($resultSet) > 0) ? $resultSet->current() : null;
    }

    public function getCount($where = [])
    {
        $Select = $this->getSql()->select();
        $Select->columns(['total' => new Expression('IFNULL(COUNT(*),0)')])->where($where);

        $row = $this->getSql()
            ->prepareStatementForSqlObject($Select)
            ->execute()
            ->current();

        return !is_null($row) ? $row['total'] : 0;
    }

    /**
     * @param $id
     *
     * @return Model
     * @throws TableGatewayException
     */
    public function getById($id)
    {
        try {
            return $this->getByField('id', $id);
        } catch (TableGatewayException $tge) {
            throw new TableGatewayException("Cannot find record by ID '{$id}'");
        }
    }

    /**
     * @param $field
     * @param $value
     *
     * @return array|\ArrayObject|null
     * @throws TableGatewayException
     */
    public function getByField($field, $value)
    {
        $row = $this->select([$field => $value])->current();
        if (!$row) {
            throw new TableGatewayException("Could not find record by ['{$field}' => '{$value}']");
        }
        return $row;
    }

    /**
     * @param array $primaryKeys
     * @return array|\ArrayObject|null
     * @throws TableGatewayException
     */
    public function getByPrimaryKey(array $primaryKeys)
    {
        $row = $this->select($primaryKeys)->current();
        if (!$row) {
            throw new TableGatewayException("Could not find record by primary keys: " . var_export($primaryKeys, true) . ".");
        }
        return $row;
    }

    /**
     * @param array $data
     * @return Model
     */
    public function getNewModelInstance(array $data = [])
    {
        $model = $this->model;
        return new $model($data);
    }

    /**
     * @param Select $select
     * @return Model[]
     */
    public function getBySelect(Select $select)
    {
        $resultSet = $this->executeSelect($select);
        $return = [];
        foreach($resultSet as $result){
            $return[] = $result;
        }
        return $return;
    }

    /**
     * @param Select $select
     * @return Model[]
     */
    public function getBySelectRaw(Select $select)
    {
        $resultSet = $this->executeSelect($select);
        $return = [];
        while($result = $resultSet->getDataSource()->current()){
            $return[] = $result;
            $resultSet->getDataSource()->next();
        }
        return $return;
    }
}
