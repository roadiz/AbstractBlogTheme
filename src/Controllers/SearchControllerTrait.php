<?php
namespace Themes\AbstractBlogTheme\Controllers;

use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Themes\AbstractBlogTheme\Model\SearchMeta;
use Themes\AbstractBlogTheme\Model\SearchResponse;
use Themes\AbstractBlogTheme\Model\SearchResult;

trait SearchControllerTrait
{

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function getQuery(Request $request)
    {
        return strip_tags($request->get($this->getSearchParamName()));
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
     * @param Request $request
     * @param string  $_locale
     * @param int     $page
     * @param string  $_format
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchAction(Request $request, $_locale, $page = 1, $_format = 'html')
    {
        $translation = $this->bindLocaleFromRoute($request, $_locale);
        $this->prepareThemeAssignation(null, $translation);
        $query = $this->getQuery($request);

        $criteria = [
            'visible' => true,
            'translation' => $translation,
            'nodeType' => $this->getSearchableTypes(),
        ];
        $numResults = $this->get('solr.search.nodeSource')
            ->count(
                $query, # Use ?q query parameter to search with
                $criteria, # a simple criteria array to filter search results
                $this->getItemsPerPage(), # result count
                true
            )
        ;
        $results = $this->get('solr.search.nodeSource')
            ->searchWithHighlight(
                $query, # Use ?q query parameter to search with
                $criteria, # a simple criteria array to filter search results
                $this->getItemsPerPage(), # result count
                true, # Search in tags too,
                10000000,
                $page
            )
        ;

        $pageCount = ceil($numResults/$this->getItemsPerPage());
        $this->assignation['results'] = $results;
        $searchMeta = new SearchMeta();
        $searchMeta->setSearch($query);
        $searchMeta->setCurrentPage($page);
        $searchMeta->setPageCount($pageCount);
        $searchMeta->setItemPerPage($this->getItemsPerPage());
        $searchMeta->setItemCount($numResults);

        if ($pageCount > $page) {
            $searchMeta->setNextPageQuery($this->generateUrl($request->attributes->get('_route'), [
                '_locale' => $_locale,
                'page' => $page + 1,
                '_format' => $_format,
                $this->getSearchParamName() => $query,
            ]));
        }
        if ($page > 1) {
            $searchMeta->setPreviousPageQuery($this->generateUrl($request->attributes->get('_route'), [
                '_locale' => $_locale,
                'page' => $page - 1,
                '_format' => $_format,
                $this->getSearchParamName() => $query,
            ]));
        }

        $this->assignation['query'] = $query;
        $this->assignation['filters'] = $searchMeta;
        $this->assignation['pageMeta'] = [
            'title' => $this->getTranslator()->trans('search'). ' – ' . $this->get('settingsBag')->get('site_name'),
            'description' => $this->getTranslator()->trans('search'),
        ];
        $results = array_map(function ($item) {
            return $this->createSearchResultModel($item);
        }, $results);
        $searchResponseModel = new SearchResponse($results, $searchMeta);
        $this->assignation['resultModels'] = $searchResponseModel->getResults();

        if ($_format === 'json') {
            /** @var Serializer $serializer */
            $serializer = $this->get('searchResults.serializer');
            $response = new Response($serializer->serialize($searchResponseModel, 'json'));
        } else {
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
        return 5;
    }
}
