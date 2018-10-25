<?php
/**
 * thehopegallery.com - SearchResponse.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 25/10/2018
 */

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
