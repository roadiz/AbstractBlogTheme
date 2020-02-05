<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

interface SearchResponseInterface
{
    /**
     * @return array
     */
    public function getResults(): array;

    /**
     * @return SearchMetaInterface
     */
    public function getMeta(): SearchMetaInterface;

    /**
     * @param array $results
     *
     * @return SearchResponseInterface
     */
    public function setResults(array $results): SearchResponseInterface;

    /**
     * @param SearchMetaInterface $meta
     *
     * @return SearchResponseInterface
     */
    public function setMeta(SearchMetaInterface $meta): SearchResponseInterface;
}
