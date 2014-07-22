<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 05.06.14
 * Time: 15:26
 */

namespace Hn\DataTablesBundle\Model;

abstract class DataTable
{
    /**
     * @var DataTableColumn[]
     */
    private $columns = array();

    /**
     * @var array
     */
    private $defaultParameters = array(
        'offset' => 1,
        'limit' => 25,
        'sorting' => array()
    );

    public function __construct()
    {
        $this->buildDataTable();
    }

    /**
     * Create the definition of the data table
     */
    protected abstract function buildDataTable();

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setDefaultParameter($name, $value)
    {
        $this->defaultParameters[$name] = $value;
    }

    /**
     * @return array
     */
    public function getDefaultParameters()
    {
        return $this->defaultParameters;
    }

    protected function addColumn($name, array $options = array())
    {
        $column = new DataTableColumn($name);

        if (array_key_exists('sortings', $options)) {
            $column->setSortings($options['sortings']);
        }

        if (array_key_exists('transformer', $options)) {
            $transformers = $options['transformer'];
            $transformers = is_array($transformers) ? $transformers : array($transformers);
            foreach ($transformers as $transformer) {
                $column->appendDataTransformer($transformer);
            }
        }

        $this->columns[$name] = $column;
    }

    /**
     * @return DataTableColumn[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param string $name
     * @return DataTableColumn|null
     */
    public function getColumn($name)
    {
        return array_key_exists($name, $this->columns)
            ? $this->columns[$name] : null;
    }

    /**
     * Returns the name of this table
     *
     * @return string
     */
    public abstract function getName();

}