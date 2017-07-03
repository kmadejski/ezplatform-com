<?php

namespace AppBundle\Controller;

use AppBundle\Form\OrderType;
use AppBundle\QueryType\BundlesQueryType;
use AppBundle\Service\Packagist\PackagistServiceProviderInterface;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Query;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class BundleController
{
    /**
     * @var \Symfony\Bundle\TwigBundle\TwigEngine
     */
    private $templating;

    /**
     * @var \eZ\Publish\API\Repository\SearchService
     */
    private $searchService;

    /**
     * @var \AppBundle\QueryType\BundlesQueryType
     */
    private $bundlesQueryType;

    /**
     * @var \AppBundle\Service\Packagist\PackagistServiceProviderInterface;
     */
    private $packagistServiceProvider;

    /**
     * @var \Symfony\Component\Form\FormFactory
     */
    private $formFactory;

    /**
     * @var int
     */
    private $bundlesListLocationId;

    /**
     * @var int
     */
    private $bundlesListCardsLimit;

    /**
     * BundleController constructor.
     * @param EngineInterface $templating
     * @param SearchService $searchService
     * @param BundlesQueryType $bundlesQueryType
     * @param PackagistServiceProviderInterface $packagistServiceProvider
     * @param FormFactory $formFactory
     * @param int $bundlesListLocationId
     * @param int $bundlesListCardsLimit
     */
    public function __construct(
        EngineInterface $templating,
        SearchService $searchService,
        BundlesQueryType $bundlesQueryType,
        PackagistServiceProviderInterface $packagistServiceProvider,
        FormFactory $formFactory,
        $bundlesListLocationId,
        $bundlesListCardsLimit
    )
    {
        $this->templating = $templating;
        $this->searchService = $searchService;
        $this->bundlesQueryType = $bundlesQueryType;
        $this->packagistServiceProvider = $packagistServiceProvider;
        $this->formFactory = $formFactory;
        $this->bundlesListLocationId = $bundlesListLocationId;
        $this->bundlesListCardsLimit = $bundlesListCardsLimit;
    }

    /**
     * Renders full view `bundle_list` with first page elements.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showBundlesListAction(Request $request)
    {
        $form = $this->formFactory->create(OrderType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $order = $form->get('order')->getData();
        }
        else {
            $order = null;
        }
        $searchResults = $this->getLocations(0, $order);
        $query = new Query();
        $criterion = new Query\Criterion\LocationId($this->bundlesListLocationId);
        $query->filter = $criterion;
        $contentSearchResult = $this->searchService->findContent($query);
        $content = $contentSearchResult->searchHits[0]->valueObject;
        $bundles = $this->getList($searchResults);

        return $this->templating->renderResponse('full/bundle_list.html.twig', [
            'items' => $bundles,
            'content' => $content,
            'viewType' => 'full',
            'extraParams' => [
                'totalCount' => $searchResults->totalCount,
                'page' => 1,
            ],
            'form' => $form->createView()
        ]);
    }

    /**
     * Renders `bundle_list` partial `list` view for given $page.
     *
     * @param $page
     * @return JsonResponse
     */
    public function getBundlesListAction($page, $order = null)
    {
        $offset = $page * $this->bundlesListCardsLimit - $this->bundlesListCardsLimit;
        $searchResults = $searchResults = $this->getLocations($offset, $order);
        $bundles = $this->getList($searchResults);

        $renderedContent = $this->templating->render('parts/bundle_list/list.html.twig', [
            'items' => $bundles,
            'viewType' => 'line',
            'extraParams' => [
                'totalCount' => $searchResults->totalCount,
                'page' => $page,
            ],
        ]);

        $showMoreButton = $searchResults->totalCount > ($offset + count($searchResults->searchHits)) ? true : false;

        return new JsonResponse([
            'html' => $renderedContent,
            'showLoadMoreButton' => $showMoreButton,
        ]);
    }

    /**
     * Prepares and runs search query.
     *
     * @param int $offset
     * @return \eZ\Publish\API\Repository\Values\Content\Search\SearchResult
     */
    private function getLocations($offset = 0, $order = null)
    {


        $query = $this->bundlesQueryType->getQuery([
            'parent_location_id' => $this->bundlesListLocationId,
            'limit' => $this->bundlesListCardsLimit,
            'offset' => $offset,
            'order' => $order
        ]);

        return $this->searchService->findLocations($query);
    }

    /**
     * Returns list of bundles with package details for given $searchResult set.
     *
     * @param $searchResults
     * @return array
     */
    private function getList($searchResults)
    {
        $bundles = [];
        foreach ($searchResults->searchHits as $searchHit) {
            $bundles[] = [
                'bundle' => $searchHit,
                'packageDetails' => $this->packagistServiceProvider->getPackageDetails($searchHit->valueObject->contentInfo->name)
            ];
        }

        return $bundles;
    }
}
