<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

class SearchMeta implements SearchMetaInterface
{
    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $search;

    /**
     * @var int
     */
    public $currentPage;

    /**
     * @var int
     */
    public $pageCount;

    /**
     * @var int
     */
    public $itemPerPage;

    /**
     * @var int
     */
    public $itemCount;

    /**
     * @var string
     */
    public $nextPageQuery;

    /**
     * @var string
     */
    public $previousPageQuery;

    /**
     * @var string
     */
    public $firstPageQuery;

    /**
     * @var string
     */
    public $lastPageQuery;

    /**
     * @var string
     */
    public $currentPageQuery;

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return SearchMetaInterface
     */
    public function setDescription($description): SearchMetaInterface
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * @param string $search
     *
     * @return SearchMetaInterface
     */
    public function setSearch($search): SearchMetaInterface
    {
        $this->search = $search;

        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @param int $currentPage
     *
     * @return SearchMetaInterface
     */
    public function setCurrentPage($currentPage): SearchMetaInterface
    {
        $this->currentPage = $currentPage;

        return $this;
    }

    /**
     * @return int
     */
    public function getPageCount()
    {
        return $this->pageCount;
    }

    /**
     * @param int $pageCount
     *
     * @return SearchMetaInterface
     */
    public function setPageCount($pageCount): SearchMetaInterface
    {
        $this->pageCount = $pageCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getItemPerPage()
    {
        return $this->itemPerPage;
    }

    /**
     * @param int $itemPerPage
     *
     * @return SearchMetaInterface
     */
    public function setItemPerPage($itemPerPage): SearchMetaInterface
    {
        $this->itemPerPage = $itemPerPage;

        return $this;
    }

    /**
     * @return int
     */
    public function getItemCount()
    {
        return $this->itemCount;
    }

    /**
     * @param int $itemCount
     *
     * @return SearchMetaInterface
     */
    public function setItemCount($itemCount): SearchMetaInterface
    {
        $this->itemCount = $itemCount;

        return $this;
    }

    /**
     * @return string
     */
    public function getNextPageQuery()
    {
        return $this->nextPageQuery;
    }

    /**
     * @param string $nextPageQuery
     *
     * @return SearchMetaInterface
     */
    public function setNextPageQuery($nextPageQuery): SearchMetaInterface
    {
        $this->nextPageQuery = $nextPageQuery;

        return $this;
    }

    /**
     * @return string
     */
    public function getPreviousPageQuery()
    {
        return $this->previousPageQuery;
    }

    /**
     * @param string $previousPageQuery
     *
     * @return SearchMetaInterface
     */
    public function setPreviousPageQuery($previousPageQuery): SearchMetaInterface
    {
        $this->previousPageQuery = $previousPageQuery;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstPageQuery()
    {
        return $this->firstPageQuery;
    }

    /**
     * @param string $firstPageQuery
     *
     * @return SearchMeta
     */
    public function setFirstPageQuery($firstPageQuery): SearchMetaInterface
    {
        $this->firstPageQuery = $firstPageQuery;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastPageQuery()
    {
        return $this->lastPageQuery;
    }

    /**
     * @param string $lastPageQuery
     *
     * @return SearchMeta
     */
    public function setLastPageQuery($lastPageQuery): SearchMetaInterface
    {
        $this->lastPageQuery = $lastPageQuery;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentPageQuery()
    {
        return $this->currentPageQuery;
    }

    /**
     * @param string $currentPageQuery
     *
     * @return SearchMeta
     */
    public function setCurrentPageQuery($currentPageQuery): SearchMetaInterface
    {
        $this->currentPageQuery = $currentPageQuery;

        return $this;
    }
}
