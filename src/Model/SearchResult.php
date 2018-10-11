<?php

namespace Themes\AbstractBlogTheme\Model;

use JMS\Serializer\Annotation as JMS;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\Translator;

/**
 * General search result model for serializing NodesSources.
 */
class SearchResult
{
    /**
     * @var array
     */
    protected $highlighting;

    /**
     * @JMS\Exclude
     * @var NodesSources
     */
    protected $nodeSource;

    /**
     * @JMS\Exclude
     * @var DocumentUrlGenerator
     */
    protected $documentUrlGenerator;

    /**
     * @JMS\Exclude
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @JMS\Exclude
     * @var Translator
     */
    protected $translator;

    /**
     * SearchResult constructor.
     *
     * @param NodesSources $nodeSource
     * @param array $highlighting Highlighting from Apache Solr search
     * @param DocumentUrlGenerator $documentUrlGenerator
     * @param UrlGeneratorInterface $urlGenerator
     * @param Translator $translator
     */
    public function __construct(
        NodesSources $nodeSource,
        $highlighting,
        DocumentUrlGenerator $documentUrlGenerator,
        UrlGeneratorInterface $urlGenerator,
        Translator $translator
    ) {
        $this->nodeSource = $nodeSource;
        $this->highlighting = $highlighting;
        $this->documentUrlGenerator = $documentUrlGenerator;
        $this->urlGenerator = $urlGenerator;
        $this->translator = $translator;
    }

    /**
     * @JMS\VirtualProperty()
     * @return string
     */
    public function getName()
    {
        return $this->nodeSource->getTitle();
    }

    /**
     * @JMS\VirtualProperty()
     * @return string
     */
    public function getNodeName()
    {
        return $this->nodeSource->getNode()->getNodeName();
    }

    /**
     * @JMS\VirtualProperty()
     * @return string
     */
    public function getPublishedAt()
    {
        return $this->nodeSource->getPublishedAt();
    }

    /**
     * @JMS\VirtualProperty()
     * @return string
     */
    public function getType()
    {
        return $this->translator->trans($this->nodeSource->getNode()->getNodeType()->getName());
    }

    /**
     * @JMS\VirtualProperty()
     * @return string
     */
    public function getUrl()
    {
        return $this->urlGenerator->generate($this->nodeSource);
    }

    /**
     * @JMS\VirtualProperty()
     * @return null|string
     */
    public function getImage()
    {
        if (method_exists($this->nodeSource, 'getImage') &&
            isset($this->nodeSource->getImage()[0])) {
            $this->documentUrlGenerator->setDocument($this->nodeSource->getImage()[0]);
            $this->documentUrlGenerator->setOptions([
                'width' => 800
            ]);
            return $this->documentUrlGenerator->getUrl();
        }

        return null;
    }
}