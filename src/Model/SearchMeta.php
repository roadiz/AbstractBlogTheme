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
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return SearchMeta
     */
    public function setDescription($description): SearchMeta
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
     * @return SearchMeta
     */
    public function setSearch($search): SearchMeta
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
     * @return SearchMeta
     */
    public function setCurrentPage($currentPage): SearchMeta
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
     * @return SearchMeta
     */
    public function setPageCount($pageCount): SearchMeta
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
     * @return SearchMeta
     */
    public function setItemPerPage($itemPerPage): SearchMeta
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
     * @return SearchMeta
     */
    public function setItemCount($itemCount): SearchMeta
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
     * @return SearchMeta
     */
    public function setNextPageQuery($nextPageQuery): SearchMeta
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
     * @return SearchMeta
     */
    public function setPreviousPageQuery($previousPageQuery): SearchMeta
    {
        $this->previousPageQuery = $previousPageQuery;

        return $this;
    }
}
