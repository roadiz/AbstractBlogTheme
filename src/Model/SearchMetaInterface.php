<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

interface SearchMetaInterface
{
    /**
     * @return string
     */
    public function getDescription();

    /**
     * @param string $description
     *
     * @return SearchMeta
     */
    public function setDescription($description): SearchMetaInterface;

    /**
     * @return string
     */
    public function getSearch();

    /**
     * @param string $search
     *
     * @return SearchMeta
     */
    public function setSearch($search): SearchMetaInterface;

    /**
     * @return int
     */
    public function getCurrentPage();

    /**
     * @param int $currentPage
     *
     * @return SearchMeta
     */
    public function setCurrentPage($currentPage): SearchMetaInterface;

    /**
     * @return int
     */
    public function getPageCount();

    /**
     * @param int $pageCount
     *
     * @return SearchMeta
     */
    public function setPageCount($pageCount): SearchMetaInterface;

    /**
     * @return int
     */
    public function getItemPerPage();

    /**
     * @param int $itemPerPage
     *
     * @return SearchMeta
     */
    public function setItemPerPage($itemPerPage): SearchMetaInterface;

    /**
     * @return int
     */
    public function getItemCount();

    /**
     * @param int $itemCount
     *
     * @return SearchMeta
     */
    public function setItemCount($itemCount): SearchMetaInterface;

    /**
     * @return string
     */
    public function getNextPageQuery();

    /**
     * @param string $nextPageQuery
     *
     * @return SearchMeta
     */
    public function setNextPageQuery($nextPageQuery): SearchMetaInterface;

    /**
     * @return string
     */
    public function getPreviousPageQuery();

    /**
     * @param string $previousPageQuery
     *
     * @return SearchMeta
     */
    public function setPreviousPageQuery($previousPageQuery): SearchMetaInterface;
}
