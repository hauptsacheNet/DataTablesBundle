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
use Symfony\Component\Form\FormView;
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
     * @var string
     */
    private $route;

    /**
     * @var array
     */
    private $requestParams;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var FormView
     */
    private $form;

    /**
     * @param DataTable $dataTable
     * @param array $params
     * @param Pagerfanta $pager
     * @param RouterInterface $router
     * @param $route
     * @param $requestParams
     * @param EntityManager $em
     */
    public function __construct(DataTable $dataTable, array $params, Pagerfanta $pager, RouterInterface $router, $route, $requestParams, EntityManager $em, FormView $form = null)
    {
        $this->dataTable = $dataTable;
        $this->params = $params;
        $this->pager = $pager;
        $this->router = $router;
        $this->route = $route;
        $this->requestParams = $requestParams;
        $this->em = $em;
        $this->form = $form;
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

    /**
     * @return DataTableColumn[]
     */
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
     * @return FormView
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return array
     * @throws \LogicException
     * @deprecated use #getRequestParams instead
     */
    protected function getUrlParams()
    {
        return $this->requestParams;
    }

    /**
     * @return string
     */
    protected function getCurrentRoute()
    {
        return $this->route;
    }

    /**
     * @return array
     */
    protected function getRequestParams()
    {
        return $this->requestParams;
    }

    /**
     * @param DataTableColumn $column
     * @return string
     */
    public function generateColumnUrl(DataTableColumn $column)
    {
        $name = $this->dataTable->getName();
        $params = $this->getRequestParams();

        if (!array_key_exists($name, $params) || !is_array($params[$name])) {
            $params[$name] = array();
        }

        $currentSortingIndex = $this->getColumnSortingIndex($column);
        if ($currentSortingIndex === null || $currentSortingIndex === '') {
            $nextSortingIndex = 0;
        } elseif (($currentSortingIndex + 1) >= count($column->getSortings())) {
            $nextSortingIndex = '';
        } else {
            $nextSortingIndex = $currentSortingIndex + 1;
        }

        $params[$name]['sorting'] = array(
            $column->getPropertyPath() => $nextSortingIndex
        );

        return $this->router->generate($this->getCurrentRoute(), $params);
    }

    /**
     * generates the html for th pager
     *
     * @return string
     */
    public function createPagerView()
    {
        $view = new TwitterBootstrap3View();

        $options = array(
            'prev_message' => '&larr;',
            'next_message' => '&rarr;',
        );

        $route = $this->getCurrentRoute();
        $params = $this->getRequestParams();
        $router = $this->router;
        $dataTableDefinition = $this->dataTable;
        $pager = $this->pager;

        return $view->render($this->pager, function ($page) use ($route, $params, $router, $dataTableDefinition, $pager) {

            $name = $dataTableDefinition->getName();
            if (!array_key_exists($name, $params) || !is_array($params[$name])) {
                $params[$name] = array();
            }

            $params[$name]['offset'] = ($page - 1) * $pager->getMaxPerPage();
            return $router->generate($route, $params);
        }, $options);
    }

    /**
     * writes table data via fputcsv to tmp file and returns the result
     *
     * @param string $delimiter
     * @param string $enclosure
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

    public function getDataTableRequestParams()
    {
        $dataTableDefinition = $this->dataTable;
        $result = array();
        $params = $this->getRequestParams();
        $name = $dataTableDefinition->getName();
        if (array_key_exists($name, $params) && is_array($params[$name])) {
            $result[$name] = $params[$name];
        }

        $form = $this->getForm();
        if ($form !== null) {
            $formName = $form->vars['name'];
            if (array_key_exists($formName, $params) && is_array($params[$formName])) {
                $result[$formName] = $params[$formName];
            }
        }

        return $result;
    }

    /**
     * @param DataTableRow $dataTableRow
     * @param DataTableColumn $column
     * @return string
     */
    public function getLink(DataTableRow $dataTableRow, DataTableColumn $column)
    {
        $link = $column->getLink();

        if (is_callable($link)) {
            $rowData = $dataTableRow->getData();
            $link = $link($this->router, $rowData);
        }

        return $link;
    }
}