<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 04.06.14
 * Time: 11:29
 */

namespace Hn\DataTablesBundle\Model;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class DataTableRow
{
    /**
     * @var DataTable
     */
    protected $dataTable;

    /**
     * @var mixed The raw Data of this row
     */
    protected $data;

    /**
     * @var PropertyAccessor
     */
    protected $pA;

    function __construct($data, DataTable $dataTable)
    {
        $this->dataTable = $dataTable;
        $this->data = $data;
        $this->pA = PropertyAccess::createPropertyAccessor();
    }

    public function getColumnValue(DataTableColumn $column)
    {
        // symfony >= 2.5 with isReadable
        if (method_exists($this->pA, 'isReadable')) {
            if (!$this->pA->isReadable($this->data, $column->getPropertyPath())) {
                return null;
            }
        }
        try {
            $value = $this->pA->getValue($this->data, $column->getPropertyPath());
        } catch (NoSuchPropertyException $e) {
            return null; // compatibility with symfony < 2.5
        }

        $value = $column->getDataTransformer()->transform($value);
        return $value;
    }

    public function getColumnType(DataTableColumn $column)
    {
        $value = $this->getColumnValue($column);
        if (!is_object($value)) {
            return gettype($value);
        } else {
            $class = new \ReflectionClass($value);
            return $class->getShortName();
        }
    }

    public function getData()
    {
        return $this->data;
    }

    public function toArray()
    {
        $result = array();
        foreach ($this->dataTable->getColumns() as $column) {
            $result[$column->getPropertyPath()] = $this->getColumnValue($column);
        }

        return $result;
    }


} 