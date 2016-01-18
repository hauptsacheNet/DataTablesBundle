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
use Hn\FilterBundle\Service\FilterServiceInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;
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

    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var FormRegistryInterface */
    private $formRegistry;

    /** @var FilterServiceInterface */
    private $filterService;

    public function __construct(
        RouterInterface $router,
        RequestStack $requestStack,
        EntityManager $em,
        FormFactoryInterface $formFactory,
        FormRegistryInterface $formRegistry,
        FilterServiceInterface $filterService = null
    )
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->em = $em;
        $this->formFactory = $formFactory;
        $this->formRegistry = $formRegistry;
        $this->filterService = $filterService;
    }

    /**
     * @return array
     */
    protected function getMasterRequestParams()
    {
        $request = $this->requestStack->getMasterRequest();
        if ($request === null) {
            return array();
        }

        $routeName = $request->get('_route');
        $route = $this->router->getRouteCollection()->get($routeName);

        $pathVariables = $route->compile()->getVariables();
        $params = $request->query->all();

        // add route params to params array
        foreach ($pathVariables as $pathVar) {

            $value = $request->get($pathVar, false);
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

    protected function createFilterForm(DataTable $dataTable, $route, array $requestParams)
    {
        $formType = $dataTable->getFilterFormType();

        if ($formType === null) {
            return null;
        }

        if (is_string($formType)) {
            if (!$this->formRegistry->hasType($formType)) {
                return null;
            }
        }

        return $this->formFactory->create($dataTable->getFilterFormType(), null, array(
            'action' => $this->router->generate($route, $requestParams),
            'method' => 'GET'
        ));
    }

    /**
     * Create the view instance
     *
     * @param DataTable $dataTable
     * @param QueryBuilder $queryBuilder
     * @param array $params
     * @return DataTableView
     */
    public function createView(DataTable $dataTable, QueryBuilder $queryBuilder = null, $params = array())
    {
        $params = array_merge($dataTable->getDefaultParameters(), $params);
        $request = $this->requestStack->getMasterRequest();

        if ($request !== null) {
            $params = array_merge($params, (array)$request->get($dataTable->getName(), array()));
        }

        // prepare doctrine query
        if ($queryBuilder === null) {
            $queryBuilder = $dataTable->createQueryBuilder($this->em);
        }
        $queryBuilder = $dataTable->applySortingToQueryBuilder($queryBuilder, $params['sorting']);

        // prepare routes
        $route = null;
        if (isset($params['route']) ) {
            $route = $params['route'];
        }
        if ($request != null) {
            $route = $request->get('_route');
        }
        $requestParams = isset($params['pass_params']) ? $params['pass_params'] : $this->getMasterRequestParams();

        // prepare filter
        $form = $this->createFilterForm($dataTable, $route, $requestParams);
        $formView = null;
        if ($form !== null) {
            $formData = $request->get($form->getName());
            if ($formData !== null) {
                $form->submit($formData);
                $this->filterService->addFilterToQueryBuilder($form->getData(), $queryBuilder);
            }
            $formView = $form->createView();
        }

        // configure pager
        $pager = $dataTable->createPager($queryBuilder);
        $pager->setMaxPerPage($params['limit']);
        $pager->setCurrentPage(max(1, min($pager->getNbPages(), 1 + floor($params['offset'] / $params['limit']))));
        // create view
        return new DataTableView($dataTable, $params, $pager, $this->router, $route, $requestParams, $this->em, $formView);
    }
} 