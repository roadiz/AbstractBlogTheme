<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Factory;

use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Models\DocumentInterface;
use RZ\Roadiz\Markdown\MarkdownInterface;
use RZ\Roadiz\Utils\UrlGenerators\DocumentUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Themes\AbstractBlogTheme\Model\HydraCollection;
use Themes\AbstractBlogTheme\Model\JsonLdArticle;
use Themes\AbstractBlogTheme\Model\JsonLdObject;
use Themes\AbstractBlogTheme\Model\JsonLdOrganization;
use Themes\AbstractBlogTheme\Model\JsonLdPerson;
use Themes\AbstractBlogTheme\Model\JsonLdPlace;

class JsonLdFactory
{
    /**
     * @var DocumentUrlGenerator
     */
    protected $documentUrlGenerator;
    /**
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;
    /**
     * @var Settings
     */
    protected $settingsBag;
    /**
     * @var array
     */
    protected $imageOptions;
    /**
     * @var MarkdownInterface|null
     */
    protected $markdown;

    /**
     * @param DocumentUrlGenerator $documentUrlGenerator
     * @param UrlGeneratorInterface $urlGenerator
     * @param Settings $settingsBag
     * @param array $imageOptions
     * @param MarkdownInterface|null $markdown
     */
    public function __construct(
        DocumentUrlGenerator $documentUrlGenerator,
        UrlGeneratorInterface $urlGenerator,
        Settings $settingsBag,
        array $imageOptions = [],
        MarkdownInterface $markdown = null
    ) {
        $this->documentUrlGenerator = $documentUrlGenerator;
        $this->urlGenerator = $urlGenerator;
        $this->settingsBag = $settingsBag;
        $this->imageOptions = $imageOptions;
        $this->markdown = $markdown;
    }

    public function createArticle(NodesSources $nodeSource): ?JsonLdObject
    {
        return new JsonLdArticle(
            $nodeSource,
            $this->documentUrlGenerator,
            $this->urlGenerator,
            $this->settingsBag,
            $this,
            $this->imageOptions,
            $this->markdown
        );
    }

    /**
     * @param NodesSources $nodeSource
     *
     * @return JsonLdOrganization|null
     */
    public function createOrganization(NodesSources $nodeSource): ?JsonLdObject
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
     * @param NodesSources $nodeSource
     *
     * @return JsonLdPlace|null
     */
    public function createPlace(NodesSources $nodeSource): ?JsonLdObject
    {
        if (method_exists($nodeSource, 'getLocation') &&
            $nodeSource->getLocation() != '') {
            return new JsonLdPlace($nodeSource->getLocation());
        }
        return null;
    }

    public function createPerson(NodesSources $nodeSource): ?JsonLdObject
    {
        if (method_exists($nodeSource, 'getAuthor') &&
            $nodeSource->getAuthor() != '') {
            return new JsonLdPerson($nodeSource->getAuthor());
        }
        return null;
    }

    /**
     * @param array $member
     * @param int   $totalItems
     * @param int   $page
     * @param int   $totalPages
     * @param string $route
     * @param array $currentParams
     *
     * @return HydraCollection|null
     */
    public function createHydraCollection(
        array $member,
        int $totalItems,
        int $page,
        int $totalPages,
        $route,
        array $currentParams
    ): ?HydraCollection {
        return new HydraCollection(
            $member,
            $totalItems,
            $page,
            $totalPages,
            $this->urlGenerator,
            $route,
            $currentParams
        );
    }

    /**
     * @return DocumentInterface|null
     */
    protected function getDefaultOrganizationLogo()
    {
        return $this->settingsBag->getDocument('admin_image');
    }
}
