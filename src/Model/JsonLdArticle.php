<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

use JMS\Serializer\Annotation as JMS;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Markdown\MarkdownInterface;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Themes\AbstractBlogTheme\Factory\JsonLdFactory;

class JsonLdArticle extends JsonLdObject
{
    /**
     * @var string
     * @JMS\SerializedName("@type")
     * @JMS\Groups({"collection"})
     */
    public static $type = "NewsArticle";

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
     * @var Settings
     */
    protected $settingsBag;

    /**
     * @JMS\Exclude
     * @var array
     */
    protected $imageOptions;
    /**
     * @JMS\Exclude
     * @var MarkdownInterface|null
     */
    protected $markdown;
    /**
     * @JMS\Exclude
     * @var JsonLdFactory
     */
    protected $jsonLdFactory;

    /**
     * JsonLdArticle constructor.
     *
     * @param NodesSources           $nodeSource
     * @param DocumentUrlGenerator   $documentUrlGenerator
     * @param UrlGeneratorInterface  $urlGenerator
     * @param Settings               $settingsBag
     * @param JsonLdFactory          $jsonLdFactory
     * @param array|null             $imageOptions
     * @param MarkdownInterface|null $markdown
     */
    public function __construct(
        NodesSources $nodeSource,
        DocumentUrlGenerator $documentUrlGenerator,
        UrlGeneratorInterface $urlGenerator,
        Settings $settingsBag,
        JsonLdFactory $jsonLdFactory,
        array $imageOptions = null,
        ?MarkdownInterface $markdown = null
    ) {
        $this->nodeSource = $nodeSource;
        $this->documentUrlGenerator = $documentUrlGenerator;
        $this->urlGenerator = $urlGenerator;
        $this->settingsBag = $settingsBag;
        $this->markdown = $markdown;

        if (null !== $imageOptions) {
            $this->imageOptions = $imageOptions;
        } else {
            $this->imageOptions = [
                'width' => 800,
            ];
        }
        $this->jsonLdFactory = $jsonLdFactory;
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"collection"})
     * @return string|null
     */
    public function getHeadline()
    {
        return $this->nodeSource->getTitle();
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Type("DateTime<'c'>")
     * @JMS\Groups({"collection"})
     * @return \DateTime|null
     */
    public function getDatePublished()
    {
        return $this->nodeSource->getPublishedAt();
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Type("DateTime<'Y'>")
     * @JMS\Groups({"collection"})
     * @return \DateTime|null
     */
    public function getCopyrightYear()
    {
        return $this->nodeSource->getPublishedAt();
    }

    /**
     * @return array
     */
    protected function getImageGenerationOptions()
    {
        return $this->imageOptions;
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"collection"})
     * @return array|null
     */
    public function getImage()
    {
        /** @var string|null $methodName */
        $methodName = null;
        if (method_exists($this->nodeSource, 'getImage')) {
            $methodName = 'getImage';
        }
        if (method_exists($this->nodeSource, 'getImages')) {
            $methodName = 'getImages';
        }
        if (null !== $methodName) {
            return array_map(function (Document $document) {
                $this->documentUrlGenerator->setDocument($document);
                $this->documentUrlGenerator->setOptions($this->getImageGenerationOptions());

                return $this->documentUrlGenerator->getUrl(true);
            }, $this->nodeSource->$methodName());
        }

        return [];
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"collection"})
     * @return JsonLdPerson|JsonLdOrganization|null
     */
    public function getAuthor()
    {
        if (method_exists($this->nodeSource, 'getAuthor') &&
            $this->nodeSource->getAuthor() != '') {
            return new JsonLdPerson($this->nodeSource->getAuthor());
        }
        return $this->jsonLdFactory->createOrganization($this->nodeSource);
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"collection"})
     * @return null|string
     */
    public function getDescription()
    {
        $methodName = null;
        if (method_exists($this->nodeSource, 'getPreview')) {
            $methodName = 'getPreview';
        } elseif (method_exists($this->nodeSource, 'getExcerpt')) {
            $methodName = 'getExcerpt';
        } elseif (method_exists($this->nodeSource, 'getDescription')) {
            $methodName = 'getDescription';
        }
        if (null !== $methodName && $this->nodeSource->$methodName() != '') {
            if (null !== $this->markdown) {
                return strip_tags($this->markdown->text($this->nodeSource->$methodName()));
            } else {
                return strip_tags($this->nodeSource->$methodName());
            }
        }
        return null;
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"collection"})
     * @return null|JsonLdOrganization
     */
    public function getCopyrightHolder()
    {
        if (method_exists($this->nodeSource, 'getCopyrightHolder') &&
            $this->nodeSource->getCopyrightHolder() != '') {
            return new JsonLdOrganization($this->nodeSource->getCopyrightHolder());
        }
        return $this->jsonLdFactory->createOrganization($this->nodeSource);
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"collection"})
     * @return null|JsonLdOrganization
     */
    public function getPublisher()
    {
        if (method_exists($this->nodeSource, 'getCopyrightHolder') &&
            $this->nodeSource->getCopyrightHolder() != '') {
            return new JsonLdOrganization($this->nodeSource->getCopyrightHolder());
        }
        return $this->jsonLdFactory->createOrganization($this->nodeSource);
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"collection"})
     * @return string|null
     */
    public function getUrl()
    {
        return $this->urlGenerator->generate(
            RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
            [
                RouteObjectInterface::ROUTE_OBJECT => $this->nodeSource,
            ],
            UrlGenerator::ABSOLUTE_URL
        );
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"collection"})
     * @return array<TagTranslation>
     */
    public function getArticleSection()
    {
        return $this->nodeSource->getNode()->getTags()->filter(function (Tag $tag) {
            return $tag->isVisible();
        })->map(function (Tag $tag) {
            $translatedTag = $tag->getTranslatedTagsByTranslation($this->nodeSource->getTranslation())->first();
            return $translatedTag ? $translatedTag->getName() : $tag->getTagName();
        })->toArray();
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Groups({"collection"})
     * @return null|JsonLdPlace
     */
    public function getContentLocation()
    {
        return $this->jsonLdFactory->createPlace($this->nodeSource);
    }

    /**
     * @return null|Document
     */
    protected function getDefaultOrganizationLogo()
    {
        return $this->settingsBag->getDocument('admin_image');
    }
}
