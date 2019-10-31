<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Controllers;

use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Themes\AbstractBlogTheme\Model\HydraCollection;
use Themes\AbstractBlogTheme\Model\JsonLdArticle;

trait JsonLdSupportTrait
{
    /**
     * @param NodesSources $nodeSource
     *
     * @return JsonLdArticle
     */
    protected function getJsonLdArticle(NodesSources $nodeSource)
    {
        return new JsonLdArticle(
            $nodeSource,
            $this->get('document.url_generator'),
            $this->get('router'),
            $this->get('settingsBag'),
            $this->getJsonLdImageOptions()
        );
    }

    protected function getJsonLdImageOptions(): array
    {
        return [
            'width' => 800,
        ];
    }
}
