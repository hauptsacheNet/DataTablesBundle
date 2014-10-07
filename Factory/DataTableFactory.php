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

    public function createView(DataTable $dataTableDefinition, QueryBuilder $queryBuilder = null, $params = array())
    {
        $params = array_merge($dataTableDefinition->getDefaultParameters(), $params);
        $request = $this->requestStack->getMasterRequest();

        if ($request) {
            $params = array_merge($params, $request->get($dataTableDefinition->getName(), array()));
        }

        if ($queryBuilder === null) {
            $queryBuilder = $dataTableDefinition->createQueryBuilder($this->em);
        }

        $dataTableDefinition->applySortingToQueryBuilder($queryBuilder, $params['sorting']);


        // configure pager
        $pager = new Pagerfanta(new DoctrineORMAdapter($queryBuilder));
        $pager->setMaxPerPage($params['limit']);
        $pager->setCurrentPage(1 + floor($params['offset'] / $params['limit']));

        return new DataTableView($dataTableDefinition, $params, $pager, $this->router, $request, $this->em);
    }
} 