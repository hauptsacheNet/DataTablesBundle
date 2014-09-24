<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 05.06.14
 * Time: 16:13
 */

namespace Hn\DataTablesBundle\Model;

use Doctrine\Common\Util\Debug;
use Doctrine\ORM\EntityManager;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\TwitterBootstrap3View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class DataTableView
{
    /**
     * @var DataTable
     */
    private $dataTable;

    /**
     * @var array
     */
    private $params;

    /**
     * @var Pagerfanta
     */
    private $pager;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param DataTable $dataTable
     * @param array $params
     * @param Pagerfanta $pager
     * @param RouterInterface $router
     * @param Request $request
     * @param EntityManager $em
     */
    public function __construct(DataTable $dataTable, array $params, Pagerfanta $pager, RouterInterface $router, Request $request = null, EntityManager $em)
    {
        $this->dataTable = $dataTable;
        $this->params = $params;
        $this->pager = $pager;
        $this->router = $router;
        $this->request = $request;
        $this->em = $em;
    }

    /**
     * @return DataTable
     */
    public function getDataTable()
    {
        return $this->dataTable;
    }

    /**
     * @return Pagerfanta
     */
    public function getPager()
    {
        return $this->pager;
    }

    public function getColumns()
    {
        return $this->dataTable->getColumns();
    }

    /**
     * @return DataTableRow[]
     */
    public function getRows()
    {
        $rows = array();
        foreach ($this->pager as $row) {
            $rows[] = new DataTableRow($row, $this->getDataTable());
        }

        return $rows;
    }

    /**
     * @return DataTableRow[]
     */
    public function getAllRows()
    {
        $rows = array();
        foreach ($this->pager->getAdapter()->getSlice(0, 32000) as $row) {
            $rows[] = new DataTableRow($row, $this->getDataTable());
        }

        return $rows;
    }

    /**
     * @param DataTableColumn $column
     * @return null|number
     */
    public function getColumnSortingIndex(DataTableColumn $column)
    {
        $currentSorting = $this->params['sorting'];
        return array_key_exists($column->getPropertyPath(), $currentSorting)
            ? $currentSorting[$column->getPropertyPath()] : null;
    }

    /**
     * @return array
     * @throws \LogicException
     */
    protected function getUrlParams()
    {
        $routeName = $this->request->get('_route');
        $route = $this->router->getRouteCollection()->get($routeName);

        $pathVariables = $route->compile()->getVariables();
        $params = $this->request->query->all();

        // add route params to params array
        foreach ($pathVariables as $pathVar) {

            $value = $this->request->get($pathVar, false);
            if ($value === false) {
                continue;
            }

            if (!is_object($value)) {
                $params[$pathVar] = $value;
                continue;
            }

            // FIXME there must be a better way than using doctrine directly
            $meta = $this->em->getClassMetadata(get_class($value));
            if ($meta === null) {
                throw new \LogicException("Only Entities are implemented for automatic url generation");
            }

            $identifier = $meta->getIdentifierValues($value);
            if (count($identifier) !== 1) {
                throw new \LogicException("Don't know how to handle " . count($identifier) . " identifiers for url generation");
            } else {
                $params[$pathVar] = reset($identifier);
            }
        }

        return $params;
    }

    /**
     * @param DataTableColumn $column
     * @return string
     */
    public function generateColumnUrl(DataTableColumn $column)
    {
        if (is_null($this->request)) {
            return '#';
        }

        $name = $this->dataTable->getName();
        $params = $this->getUrlParams();

        if (!array_key_exists($name, $params) || !is_array($params[$name])) {
            $params[$name] = array();
        }

        $currentSortingIndex = $this->getColumnSortingIndex($column);
        $nextSortingIndex = $currentSortingIndex === null
            ? 0 : ($currentSortingIndex + 1) % count($column->getSortings());

        $params[$name]['sorting'] = array(
            $column->getPropertyPath() => $nextSortingIndex
        );
        return $this->router->generate($this->request->get('_route'), $params);
    }

    /**
     * generates the html for th pager
     *
     * @return string
     */
    public function createPagerView()
    {
        if (is_null($this->request)) {
            return '';
        }
        $view = new TwitterBootstrap3View();

        $options = array(
            'prev_message' => '&larr;',
            'next_message' => '&rarr;',
        );

        $request = $this->request;
        $router = $this->router;
        $dataTableDefinition = $this->dataTable;
        $pager = $this->pager;

        $params = $this->getUrlParams();

        return $view->render($this->pager, function ($page) use ($request, $router, $dataTableDefinition, $pager, $params) {

            $name = $dataTableDefinition->getName();
            if (!array_key_exists($name, $params) || !is_array($params[$name])) {
                $params[$name] = array();
            }

            $params[$name]['offset'] = ($page - 1) * $pager->getMaxPerPage();
            return $router->generate($request->get('_route'), $params);
        }, $options);
    }

    /**
     * writes table data via fputcsv to tmp file and returns the result
     *
     * @return string
     */
    public function toCsv($delimiter = ',', $enclosure = '"')
    {
        $fh = fopen('php://temp', 'w+');

        for ($page = 1; $page <= $this->getPager()->getNbPages(); $page++) {
            $this->getPager()->setCurrentPage($page);
            foreach ($this->getAllRows() as $row) {
                $data = $row->toArray();
                fputcsv($fh, $data, $delimiter, $enclosure);
            }
        }

        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return mb_convert_encoding($csv, "Windows-1252", 'UTF-8');
    }
}