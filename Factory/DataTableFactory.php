<?php
/**
 * Created by PhpStorm.
 * User: kay
 * Date: 05.06.14
 * Time: 15:02
 */

namespace Hn\DataTablesBundle\Factory;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Hn\DataTablesBundle\Model\DataTable;
use Hn\DataTablesBundle\Model\DataTableView;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class DataTableFactory
{
    /** @var RouterInterface */
    private $router;

    /** @var RequestStack */
    private $requestStack;

    /** @var EntityManager */
    private $em;

    public function __construct(RouterInterface $router, RequestStack $requestStack, EntityManager $em)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->em = $em;
    }

    public function createView(DataTable $dataTableDefinition, QueryBuilder $queryBuilder, $params = array())
    {
        $params = array_merge($dataTableDefinition->getDefaultParameters(), $params);
        $request = $this->requestStack->getMasterRequest();
        $params = array_merge($params, $request->get($dataTableDefinition->getName(), array()));
        // apply sorting
        foreach ($params['sorting'] as $sortColumn => $sortIndex) {
            $column = $dataTableDefinition->getColumn($sortColumn);
            if ($column !== null) {
                $sorting = $column->getSorting($sortIndex);
                foreach ($sorting as $field => $direction) {
                    $queryBuilder->addOrderBy($field, $direction);
                }
            }
        }

        // configure pager
        $pager = new Pagerfanta(new DoctrineORMAdapter($queryBuilder));
        $pager->setMaxPerPage($params['limit']);
        $pager->setCurrentPage(1 + floor($params['offset'] / $params['limit']));

        return new DataTableView($dataTableDefinition, $params, $pager, $this->router, $request, $this->em);
    }
} 