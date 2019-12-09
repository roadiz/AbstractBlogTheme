<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Controllers;

use RZ\Roadiz\Core\Entities\NodesSources;
use Themes\AbstractBlogTheme\Factory\JsonLdFactory;
use Themes\AbstractBlogTheme\Model\JsonLdArticle;

trait JsonLdSupportTrait
{
    /**
     * @param NodesSources $nodeSource
     *
     * @return JsonLdArticle
     * @deprecated Use JsonLdFactory service
     */
    protected function getJsonLdArticle(NodesSources $nodeSource)
    {
        return $this->get(JsonLdFactory::class)->createArticle($nodeSource);
    }

    /**
     * @return array
     * @deprecated Use jsonld.defaultImageOptions Container service
     */
    protected function getJsonLdImageOptions(): array
    {
        return $this->get('jsonld.defaultImageOptions');
    }
}
