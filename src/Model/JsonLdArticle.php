<?php
namespace Themes\AbstractBlogTheme\Model;

use JMS\Serializer\Annotation as JMS;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
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
     * AmpArticle constructor.
     *
     * @param NodesSources          $nodeSource
     * @param DocumentUrlGenerator  $documentUrlGenerator
     * @param UrlGeneratorInterface $urlGenerator
     * @param Settings              $settingsBag
     */
    public function __construct(
        NodesSources $nodeSource,
        DocumentUrlGenerator $documentUrlGenerator,
        UrlGeneratorInterface $urlGenerator,
        Settings $settingsBag
    ) {
        $this->nodeSource = $nodeSource;
        $this->documentUrlGenerator = $documentUrlGenerator;
        $this->urlGenerator = $urlGenerator;
        $this->settingsBag = $settingsBag;
    }

    /**
     * @JMS\VirtualProperty()
     * @return string
     */
    public function getHeadline()
    {
        return $this->nodeSource->getTitle();
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Type("DateTime<'c'>")
     * @return \DateTime
     */
    public function getDatePublished()
    {
        return $this->nodeSource->getPublishedAt();
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\Type("DateTime<'Y'>")
     * @return \DateTime
     */
    public function getCopyrightYear()
    {
        return $this->nodeSource->getPublishedAt();
    }

    /**
     * @JMS\VirtualProperty()
     * @return array
     */
    public function getImage()
    {
        if (method_exists($this->nodeSource, 'getImage')) {
            return array_map(function (Document $document) {
                $this->documentUrlGenerator->setDocument($document);
                $this->documentUrlGenerator->setOptions([
                    'width' => 800,
                ]);

                return $this->documentUrlGenerator->getUrl(true);
            }, $this->nodeSource->getImage());
        }

        return [];
    }

    /**
     * @JMS\VirtualProperty()
     * @return JsonLdPerson|JsonLdOrganization
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
        if (method_exists($this->nodeSource, 'getExcerpt') &&
            $this->nodeSource->getExcerpt() != '') {
            return strip_tags(\Parsedown::instance()->text($this->nodeSource->getExcerpt()));
        } elseif (method_exists($this->nodeSource, 'getDescription') &&
            $this->nodeSource->getDescription() != '') {
            return strip_tags(\Parsedown::instance()->text($this->nodeSource->getDescription()));
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
     * @return string
     */
    public function getUrl()
    {
        return $this->urlGenerator->generate($this->nodeSource, [], UrlGenerator::ABSOLUTE_URL);
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
