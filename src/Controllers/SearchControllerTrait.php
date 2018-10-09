<?php
namespace Themes\AbstractBlogTheme\Controllers;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait SearchControllerTrait
{

    /**
     * @return string
     */
    protected function getQuery()
    {
        return strip_tags($request->get($this->getSearchParamName()));
    }

    /**
     * @param Request $request
     * @param $_locale
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchAction(Request $request, $_locale, $page = 1)
    {
        $translation = $this->bindLocaleFromRoute($request, $_locale);
        $this->prepareThemeAssignation(null, $translation);

        $criteria = [
            'visible' => true,
            'translation' => $translation,
            'nodeType' => $this->getSearchableTypes(),
        ];
        $numResults = $this->get('solr.search.nodeSource')
            ->count(
                $this->getQuery(), # Use ?q query parameter to search with
                $criteria, # a simple criteria array to filter search results
                $this->getItemsPerPage(), # result count
                true
            )
        ;
        $results = $this->get('solr.search.nodeSource')
            ->searchWithHighlight(
                $this->getQuery(), # Use ?q query parameter to search with
                $criteria, # a simple criteria array to filter search results
                $this->getItemsPerPage(), # result count
                true, # Search in tags too,
                10000000,
                $page
            )
        ;

        $this->assignation['results'] = $results;
        $this->assignation['filters'] =  [
            'description' => '',
            'search' => $this->getQuery(),
            'currentPage' => $page,
            'pageCount' => ceil($numResults/$this->getItemsPerPage()),
            'itemPerPage' => $this->getItemsPerPage(),
            'itemCount' => $numResults,
            'nextPageQuery' => null,
            'previousPageQuery' => null,
        ];
        $this->assignation['query'] = $this->getQuery();
        $this->assignation['pageMeta'] = [
            'title' => $this->getTranslator()->trans('search'). ' – ' . $this->get('settingsBag')->get('site_name'),
            'description' => $this->getTranslator()->trans('search'),
        ];

        $response = $this->render($this->getTemplate(), $this->assignation, null, '/');

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
