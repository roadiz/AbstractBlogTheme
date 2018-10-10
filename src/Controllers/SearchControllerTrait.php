<?php
namespace Themes\AbstractBlogTheme\Controllers;

use Doctrine\ORM\Tools\Pagination\Paginator;
use JMS\Serializer\Serializer;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractBlogTheme\Model\SearchResult;

trait SearchControllerTrait
{

    /**
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
     * @param $_locale
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
        $this->assignation['filters'] =  [
            'description' => '',
            'search' => $query,
            'currentPage' => $page,
            'pageCount' => $pageCount,
            'itemPerPage' => $this->getItemsPerPage(),
            'itemCount' => $numResults,
            'nextPageQuery' => null,
            'previousPageQuery' => null,
        ];
        if ($pageCount > $page) {
            $this->assignation['filters']['nextPageQuery'] = $this->generateUrl($request->attributes->get('_route'), [
                '_locale' => $_locale,
                'page' => $page + 1,
                '_format' => $_format,
                $this->getSearchParamName() => $query,
            ]);
        }
        if ($page > 1) {
            $this->assignation['filters']['nextPageQuery'] = $this->generateUrl($request->attributes->get('_route'), [
                '_locale' => $_locale,
                'page' => $page - 1,
                '_format' => $_format,
                $this->getSearchParamName() => $query,
            ]);
        }

        $this->assignation['query'] = $query;
        $this->assignation['pageMeta'] = [
            'title' => $this->getTranslator()->trans('search'). ' – ' . $this->get('settingsBag')->get('site_name'),
            'description' => $this->getTranslator()->trans('search'),
        ];

        if ($_format === 'json') {
            /** @var Serializer $serializer */
            $serializer = $this->get('searchResults.serializer');
            $results = array_map(function ($item) {
                return $this->createSearchResultModel($item);
            }, $results);
            $response = new JsonResponse([
                'results' => $serializer->toArray($results),
                'filters' => $this->assignation['filters']
            ]);
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
