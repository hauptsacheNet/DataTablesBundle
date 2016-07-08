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

    protected function resolveColumn($columnName)
    {
        foreach ($this->dataTable->getColumns() as $column) {
            if ($column->getName() === $columnName || $column->getPropertyPath() === $columnName) {
                return $column;
            }
        }

        throw new \RuntimeException("Failed to resolve column '$columnName'");
    }

    public function getColumnValue($column)
    {
        if (!$column instanceof DataTableColumn) {
            $column = $this->resolveColumn($column);
        }

        $fixedValue = $column->getFixedValue();
        if ($fixedValue) {
            if (is_callable($fixedValue)) {
                return $fixedValue($this->data);
            } else {
                return $fixedValue;
            }
        }

        // virtual values must not be requested
        if ($column->isVirtual()) {
            return null;
        }

        $value = null;

        // symfony >= 2.5 with isReadable
        if (method_exists($this->pA, 'isReadable')) {
            if ($this->pA->isReadable($this->data, $column->getPropertyPath())) {
                $value = $this->pA->getValue($this->data, $column->getPropertyPath());
            }
        } else {
            try {
                $value = $this->pA->getValue($this->data, $column->getPropertyPath());
            } catch (NoSuchPropertyException $e) {
                // compatibility with symfony < 2.5
            }
        }

        $callback = $column->getCallback();
        if (is_callable($callback)) {
            $value = $callback($this->data, $value);
        }

        $value = $column->getDataTransformer()->transform($value);

        return $value;
    }

    public function getColumnType($column)
    {
        if (!$column instanceof DataTableColumn) {
            $column = $this->resolveColumn($column);
        }

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