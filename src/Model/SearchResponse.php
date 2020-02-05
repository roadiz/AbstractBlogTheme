<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

class SearchResponse implements SearchResponseInterface
{
    /**
     * @var array
     */
    protected $results;

    /**
     * @var SearchMeta
     */
    protected $meta;

    /**
     * SearchResponse constructor.
     *
     * @param array      $results
     * @param SearchMeta $meta
     */
    public function __construct(array $results = [], SearchMeta $meta = null)
    {
        $this->results = $results;
        $this->meta = $meta;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @return SearchMeta
     */
    public function getMeta(): SearchMeta
    {
        return $this->meta;
    }

    /**
     * @param array $results
     *
     * @return SearchResponseInterface
     */
    public function setResults(array $results): SearchResponseInterface
    {
        $this->results = $results;
        return $this;
    }

    /**
     * @param SearchMetaInterface $meta
     *
     * @return SearchResponseInterface
     */
    public function setMeta(SearchMetaInterface $meta): SearchResponseInterface
    {
        $this->meta = $meta;
        return $this;
    }
}
