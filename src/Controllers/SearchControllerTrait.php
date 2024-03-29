<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Controllers;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\SearchEngine\NodeSourceSearchHandlerInterface;
use RZ\Roadiz\Core\SearchEngine\SearchResultsInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Themes\AbstractBlogTheme\Model\SearchMeta;
use Themes\AbstractBlogTheme\Model\SearchMetaInterface;
use Themes\AbstractBlogTheme\Model\SearchResponse;
use Themes\AbstractBlogTheme\Model\SearchResponseInterface;
use Themes\AbstractBlogTheme\Model\SearchResult;

trait SearchControllerTrait
{
    /**
     * @return int
     */
    protected function getHighlightingFragmentSize(): int
    {
        return 150;
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function getQuery(Request $request)
    {
        return strip_tags((string) $request->get($this->getSearchParamName(), ''));
    }

    /**
     * @param $searchResult
     *
     * @return SearchResult
     */
    protected function createSearchResultModel($searchResult)
    {
        return new SearchResult(
            $searchResult['nodeSource'],
            $searchResult['highlighting'],
            $this->get('document.url_generator'),
            $this->get('router'),
            $this->get('translator')
        );
    }

    /**
     * @param TranslationInterface $translation
     * @return array
     */
    protected function getDefaultCriteria(TranslationInterface $translation): array
    {
        return [
            'visible' => true,
            'translation' => $translation,
            'nodeType' => $this->getSearchableTypes(),
        ];
    }

    protected function getDefaultPaginationParams(Request $request): array
    {
        return [
            '_locale' => $request->get('_locale', 'en'),
            '_format' => $request->get('_format', 'html'),
            $this->getSearchParamName() => $this->getQuery($request),
        ];
    }

    protected function getSerializationGroups(): array
    {
        return ['search_result', 'tag_base', 'collection', 'urls', 'highlighting'];
    }

    /**
     * @return SerializationContext
     */
    protected function getSerializationContext(): SerializationContext
    {
        $context = SerializationContext::create()
            ->setAttribute('translation', $this->getTranslation())
            ->enableMaxDepthChecks();
        if (count($this->getSerializationGroups()) > 0) {
            $context->setGroups($this->getSerializationGroups());
        }

        return $context;
    }

    /**
     * @return NodeSourceSearchHandlerInterface|null
     */
    protected function getSearchHandler(): ?NodeSourceSearchHandlerInterface
    {
        return $this->get(NodeSourceSearchHandlerInterface::class);
    }

    /**
     * @param NodeSourceSearchHandlerInterface $searchHandler
     * @param string $query
     * @param array $criteria
     * @param int $page
     * @return SearchResultsInterface
     */
    protected function doSearch(
        NodeSourceSearchHandlerInterface $searchHandler,
        string $query,
        array $criteria = [],
        int $page = 1
    ): SearchResultsInterface {
        /*
         * Query must be longer than 3 chars or Solr might crash
         * on highlighting fields.
         */
        if (strlen($query) > 3) {
            return $searchHandler->searchWithHighlight(
                $query, # Use ?q query parameter to search with
                $criteria, # a simple criteria array to filter search results
                $this->getItemsPerPage(), # result count
                true, # Search in tags too,
                10000000,
                $page
            );
        }
        return $searchHandler->search(
            $query, # Use ?q query parameter to search with
            $criteria, # a simple criteria array to filter search results
            $this->getItemsPerPage(), # result count
            true, # Search in tags too,
            10000000,
            $page
        );
    }

    /**
     * @param Request $request
     * @param string  $_format
     *
     * @return Response
     */
    public function searchAction(Request $request, $_format = 'html')
    {
        $_locale = $request->get('_locale', 'en');
        $page = (int) $request->get('page', 1);
        $translation = $this->bindLocaleFromRoute($request, $_locale);
        $this->prepareThemeAssignation(null, $translation);
        $query = $this->getQuery($request);

        $searchHandler = $this->getSearchHandler();
        if (null === $searchHandler) {
            throw new HttpException(Response::HTTP_SERVICE_UNAVAILABLE, 'Search engine does not respond.');
        }
        $searchHandler->boostByPublicationDate();
        if ($this->getHighlightingFragmentSize() > 0) {
            $searchHandler->setHighlightingFragmentSize($this->getHighlightingFragmentSize());
        }

        $results = $this->doSearch(
            $searchHandler,
            $query,
            $this->getDefaultCriteria($translation),
            $page
        );

        $pageCount = ceil($results->getResultCount()/$this->getItemsPerPage());
        $searchMeta = $this->createSearchMetaInstance();
        $searchMeta->setSearch($query);
        $searchMeta->setCurrentPage($page);
        $searchMeta->setPageCount($pageCount);
        $searchMeta->setItemPerPage($this->getItemsPerPage());
        $searchMeta->setItemCount($results->getResultCount());

        $searchMeta->setCurrentPageQuery($this->generateUrl(
            $request->attributes->get('_route'),
            array_merge($this->getDefaultPaginationParams($request), [
                'page' => $page,
            ])
        ));

        if ($pageCount > 1) {
            $searchMeta->setLastPageQuery($this->generateUrl(
                $request->attributes->get('_route'),
                array_merge($this->getDefaultPaginationParams($request), [
                    'page' => $pageCount,
                ])
            ));
            $searchMeta->setFirstPageQuery($this->generateUrl(
                $request->attributes->get('_route'),
                array_merge($this->getDefaultPaginationParams($request), [
                    'page' => 1,
                ])
            ));
        }

        if ($pageCount > $page) {
            $searchMeta->setNextPageQuery($this->generateUrl(
                $request->attributes->get('_route'),
                array_merge($this->getDefaultPaginationParams($request), [
                    'page' => $page + 1,
                ])
            ));
        }
        if ($page > 1) {
            $searchMeta->setPreviousPageQuery($this->generateUrl(
                $request->attributes->get('_route'),
                array_merge($this->getDefaultPaginationParams($request), [
                    'page' => $page - 1,
                ])
            ));
        }

        $this->assignation['query'] = $query;
        $this->assignation['filters'] = $searchMeta;
        $this->assignation['pageMeta'] = [
            'title' => $this->getTranslator()->trans('search'). ' – ' . $this->getSettingsBag()->get('site_name'),
            'description' => $this->getTranslator()->trans('search'),
        ];
        $resultModels = $results->map(function ($item) {
            return $this->createSearchResultModel($item);
        });

        $searchResponseModel = $this->createSearchResponseInstance();
        $searchResponseModel->setMeta($searchMeta);
        $searchResponseModel->setResults($resultModels);
        $this->assignation['resultModels'] = $searchResponseModel->getResults();

        if ($_format === 'json') {
            /** @var Serializer $serializer */
            $serializer = $this->get('serializer');
            $response = new Response($serializer->serialize(
                $searchResponseModel,
                'json',
                $this->getSerializationContext()
            ));
        } else {
            $this->assignation['results'] = $results->getResultItems();
            $response = $this->render($this->getTemplate(), $this->assignation, null, '/');
        }


        if ($this->getResponseTtl() > 0) {
            /*
             * Set http cache for current request
             * only if prod mode.
             *
             * Be careful! Do not use cache
             * if page contains form and user content!
             */
            return $this->makeResponseCachable($request, $response, $this->getResponseTtl());
        }

        return $response;
    }

    protected function createSearchMetaInstance(): SearchMetaInterface
    {
        return new SearchMeta();
    }

    protected function createSearchResponseInstance(): SearchResponseInterface
    {
        return new SearchResponse();
    }

    /**
     * @return int
     */
    public function getItemsPerPage()
    {
        if ($this->get('kernel')->isDebug()) {
            return 3;
        }
        return 15;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return 'pages/search.html.twig';
    }

    /**
     * @return string
     */
    protected function getSearchParamName()
    {
        return 'q';
    }

    /**
     * @return array
     */
    protected function getSearchableTypes()
    {
        return [
            $this->get('nodeTypesBag')->get('Page'),
            $this->get('nodeTypesBag')->get('BlogPost'),
        ];
    }

    /**
     * @return int
     */
    public function getResponseTtl()
    {
        if (null !== $this->nodeSource) {
            return $this->nodeSource->getNode()->getTtl();
        }
        return 5;
    }
}
