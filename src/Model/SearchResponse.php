<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

use JMS\Serializer\Annotation as JMS;

class SearchResponse implements SearchResponseInterface
{
    /**
     * @var array
     * @JMS\Groups({"collection"})
     */
    protected array $results;

    /**
     * @var SearchMetaInterface|null
     * @JMS\Groups({"collection"})
     */
    protected ?SearchMetaInterface $meta;

    /**
     * @param array      $results
     * @param SearchMetaInterface|null $meta
     */
    public function __construct(array $results = [], SearchMetaInterface $meta = null)
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
     * @return SearchMetaInterface|null
     */
    public function getMeta(): ?SearchMetaInterface
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
