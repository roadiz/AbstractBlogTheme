<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

class SearchResponse
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
    public function __construct(array $results, SearchMeta $meta)
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
}
