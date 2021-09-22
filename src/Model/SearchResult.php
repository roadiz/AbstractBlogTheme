<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

use JMS\Serializer\Annotation as JMS;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\Translator;

/**
 * General search result model for serializing NodesSources.
 */
class SearchResult
{
    /**
     * @var array
     * @JMS\Groups({"highlighting"})
     */
    protected $highlighting;

    /**
     * @JMS\Exclude
     * @var NodesSources
     */
    protected NodesSources $nodeSource;

    /**
     * @JMS\Exclude
     * @var DocumentUrlGenerator
     */
    protected DocumentUrlGenerator $documentUrlGenerator;

    /**
     * @JMS\Exclude
     * @var UrlGeneratorInterface
     */
    protected UrlGeneratorInterface $urlGenerator;

    /**
     * @JMS\Exclude
     * @var Translator
     */
    protected Translator $translator;

    /**
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
     * @JMS\Groups({"search_result"})
     * @return string
     */
    public function getName()
    {
        return $this->nodeSource->getTitle();
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"search_result"})
     * @return string|null
     */
    public function getNodeName()
    {
        if (null !== $this->nodeSource->getNode()) {
            return $this->nodeSource->getNode()->getNodeName();
        }
        return null;
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"search_result"})
     * @return \DateTime|null
     */
    public function getPublishedAt()
    {
        return $this->nodeSource->getPublishedAt();
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"search_result"})
     * @return string|null
     */
    public function getType()
    {
        if (null !== $this->nodeSource->getNode() && null !== $this->nodeSource->getNode()->getNodeType()) {
            return $this->translator->trans($this->nodeSource->getNode()->getNodeType()->getName());
        }
        return null;
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"search_result"})
     * @return string
     */
    public function getUrl()
    {
        return $this->urlGenerator->generate(
            RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
            [
                RouteObjectInterface::ROUTE_OBJECT => $this->nodeSource,
            ]
        );
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"search_result"})
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

    /**
     * @return array
     */
    public function getHighlighting()
    {
        return $this->highlighting;
    }

    /**
     * @return NodesSources
     */
    public function getNodeSource()
    {
        return $this->nodeSource;
    }
}
