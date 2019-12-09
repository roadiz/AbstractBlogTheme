<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

use JMS\Serializer\Annotation as JMS;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class JsonLdArticle
{
    /**
     * @JMS\SerializedName("@context")
     * @var string
     */
    public static $context = "http://schema.org";

    /**
     * @var string
     * @JMS\SerializedName("@type")
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
     * JsonLdArticle constructor.
     *
     * @param NodesSources          $nodeSource
     * @param DocumentUrlGenerator  $documentUrlGenerator
     * @param UrlGeneratorInterface $urlGenerator
     * @param Settings              $settingsBag
     * @param array|null            $imageOptions
     */
    public function __construct(
        NodesSources $nodeSource,
        DocumentUrlGenerator $documentUrlGenerator,
        UrlGeneratorInterface $urlGenerator,
        Settings $settingsBag,
        array $imageOptions = null
    ) {
        $this->nodeSource = $nodeSource;
        $this->documentUrlGenerator = $documentUrlGenerator;
        $this->urlGenerator = $urlGenerator;
        $this->settingsBag = $settingsBag;

        if (null !== $imageOptions) {
            $this->imageOptions = $imageOptions;
        } else {
            $this->imageOptions = [
                'width' => 800,
            ];
        }
    }

    /**
     * @JMS\VirtualProperty()
     * @return string|null
     */
    public function getHeadline()
    {
        return $this->nodeSource->getTitle();
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Type("DateTime<'c'>")
     * @return \DateTime|null
     */
    public function getDatePublished()
    {
        return $this->nodeSource->getPublishedAt();
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Type("DateTime<'Y'>")
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
     * @return JsonLdPerson|JsonLdOrganization|null
     */
    public function getAuthor()
    {
        if (method_exists($this->nodeSource, 'getAuthor') &&
            $this->nodeSource->getAuthor() != '') {
            return new JsonLdPerson($this->nodeSource->getAuthor());
        }
        return $this->getDefaultOrganization();
    }

    /**
     * @JMS\VirtualProperty()
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
            if (class_exists('\Parsedown')) {
                return strip_tags(\Parsedown::instance()->text($this->nodeSource->$methodName()));
            } else {
                return strip_tags($this->nodeSource->$methodName());
            }
        }
        return null;
    }

    /**
     * @JMS\VirtualProperty()
     * @return null|JsonLdOrganization
     */
    public function getCopyrightHolder()
    {
        if (method_exists($this->nodeSource, 'getCopyrightHolder') &&
            $this->nodeSource->getCopyrightHolder() != '') {
            return new JsonLdOrganization($this->nodeSource->getCopyrightHolder());
        }
        return $this->getDefaultOrganization();
    }

    /**
     * @JMS\VirtualProperty()
     * @return null|JsonLdOrganization
     */
    public function getPublisher()
    {
        if (method_exists($this->nodeSource, 'getCopyrightHolder') &&
            $this->nodeSource->getCopyrightHolder() != '') {
            return new JsonLdOrganization($this->nodeSource->getCopyrightHolder());
        }
        return $this->getDefaultOrganization();
    }

    /**
     * @JMS\VirtualProperty()
     * @return string|null
     */
    public function getUrl()
    {
        return $this->urlGenerator->generate($this->nodeSource, [], UrlGenerator::ABSOLUTE_URL);
    }

    /**
     * @JMS\VirtualProperty()
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
     * @return null|JsonLdPlace
     */
    public function getContentLocation()
    {
        if (method_exists($this->nodeSource, 'getLocation') &&
            $this->nodeSource->getLocation() != '') {
            return new JsonLdPlace($this->nodeSource->getLocation());
        }

        return null;
    }

    /**
     * @return JsonLdOrganization Create a default Organization using Roadiz site_name and admin_image settings.
     */
    protected function getDefaultOrganization()
    {
        /** @var Document|null $logoDocument */
        $logoDocument = $this->getDefaultOrganizationLogo();
        if (null !== $logoDocument) {
            $this->documentUrlGenerator->setDocument($logoDocument);
            $this->documentUrlGenerator->setOptions([
                'noProcess' => true,
            ]);
            $logoDocumentUrl = $this->documentUrlGenerator->getUrl(true);
        } else {
            $logoDocumentUrl = '';
        }

        return new JsonLdOrganization(
            $this->settingsBag->get('site_name'),
            $logoDocumentUrl
        );
    }

    /**
     * @return null|Document
     */
    protected function getDefaultOrganizationLogo()
    {
        return $this->settingsBag->getDocument('admin_image');
    }
}
