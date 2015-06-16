<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 05.06.14
 * Time: 15:26
 */

namespace Hn\DataTablesBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Form\FormTypeInterface;

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

        if (array_key_exists('template', $options)) {
            $column->setTemplate($options['template']);
        }

        if (array_key_exists('virtual', $options)) {
            $column->setVirtual($options['virtual']);
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
     * Can automatically create a query builder from an entity manager.
     * This can be used to join stuff beforehand.
     *
     * @param EntityManager $entityManager
     * @throws \LogicException if not implemented
     * @returns QueryBuilder
     */
    public function createQueryBuilder(EntityManager $entityManager)
    {
        throw new \LogicException("create query builder not implemented");
    }

    public function applySortingToQueryBuilder(QueryBuilder $queryBuilder, $sorting)
    {

        // apply sorting
        foreach ($sorting as $sortColumn => $sortIndex) {
            $column = $this->getColumn($sortColumn);
            if ($column !== null) {
                $sorting = $column->getSorting($sortIndex);
                foreach ($sorting as $field => $direction) {
                    $queryBuilder->addOrderBy($field, $direction);
                }
            }
        }

        return $queryBuilder;
    }

    /**
     * Returns the name of this table
     *
     * @return string
     */
    public abstract function getName();

    /**
     * Returns a possible form type to show as filter.
     *
     * @return string|FormTypeInterface
     */
    public function getFilterFormType()
    {
        return $this->getName() . '_filter';
    }


    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return Pagerfanta
     */
    public function createPager(QueryBuilder $queryBuilder)
    {
        return new Pagerfanta(new DoctrineORMAdapter($queryBuilder));
    }

}