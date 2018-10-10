<?php

namespace Themes\AbstractBlogTheme\Model;

use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SearchResult
{
    /**
     * @var array
     */
    protected $highlighting;

    /**
     * @Serializer\Exclude
     * @var NodesSources
     */
    protected $nodeSource;

    /**
     * @Serializer\Exclude
     * @var DocumentUrlGenerator
     */
    protected $documentUrlGenerator;

    /**
     * @Serializer\Exclude
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * SearchResult constructor.
     *
     * @param NodesSources $nodeSource
     * @param DocumentUrlGenerator $documentUrlGenerator
     */
    public function __construct(
        NodesSources $nodeSource,
        $highlighting,
        DocumentUrlGenerator $documentUrlGenerator,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->nodeSource = $nodeSource;
        $this->highlighting = $highlighting;
        $this->documentUrlGenerator = $documentUrlGenerator;
        $this->urlGenerator = $urlGenerator;
    }

    public function getName()
    {
        return $this->nodeSource->getTitle();
    }

    public function getImage()
    {
        if (isset($this->nodeSource->getImage()[0])) {
            $this->documentUrlGenerator->setDocument($this->nodeSource->getImage()[0]);
            $this->documentUrlGenerator->setOptions([
                'width' => 800
            ]);
            return $this->documentUrlGenerator->getUrl();
        }

        return null;
    }
}
