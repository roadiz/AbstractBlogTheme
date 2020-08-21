<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HydraCollection
{
    /**
     * @var string
     * @JMS\SerializedName("@type")
     * @JMS\Groups({"collection"})
     */
    public static $type = "hydra:Collection";

    /**
     * @var array
     * @JMS\SerializedName("hydra:member")
     * @JMS\Groups({"collection"})
     */
    protected $member = [];

    /**
     * @var int
     * @JMS\SerializedName("hydra:totalItems")
     * @JMS\Groups({"collection"})
     */
    protected $totalItems;

    /**
     * @var int
     * @JMS\Exclude
     */
    protected $page = 1;

    /**
     * @var int
     * @JMS\Exclude
     */
    protected $totalPages = 1;

    /**
     * @JMS\Exclude
     * @var UrlGeneratorInterface
     */
    protected $urlGenerator;

    /**
     * @JMS\Exclude
     * @var string
     */
    protected $route;

    /**
     * @var array
     * @JMS\Exclude
     */
    protected $currentParams = [];

    /**
     * HydraCollection constructor.
     *
     * @param array $member
     * @param int $totalItems
     * @param int $page
     * @param int $totalPages
     * @param UrlGeneratorInterface $urlGenerator
     * @param string $route
     * @param array $currentParams
     */
    public function __construct(
        array $member,
        int $totalItems,
        int $page,
        int $totalPages,
        UrlGeneratorInterface $urlGenerator,
        $route,
        array $currentParams
    ) {
        $this->member = $member;
        $this->totalItems = $totalItems;
        $this->page = $page;
        $this->totalPages = $totalPages;
        $this->urlGenerator = $urlGenerator;
        $this->route = $route;
        $this->currentParams = $currentParams;

        if (isset($this->currentParams['node'])) {
            unset($this->currentParams['node']);
        }
        if (isset($this->currentParams['translation'])) {
            unset($this->currentParams['translation']);
        }
        if (isset($this->currentParams['theme'])) {
            unset($this->currentParams['theme']);
        }
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\SerializedName("@id")
     * @JMS\Groups({"collection"})
     * @return string
     */
    public function getIdentifier()
    {
        return $this->urlGenerator->generate($this->route, array_merge($this->currentParams, [
            '_format' => 'json'
        ]));
    }

    /**
     * @JMS\VirtualProperty()
     * @JMS\SerializedName("hydra:view")
     * @JMS\Groups({"collection"})
     * @return array
     */
    public function getHydraView()
    {
        return [
            '@id' => $this->urlGenerator->generate($this->route, array_merge($this->currentParams, [
                '_format' => 'json',
                'page' => $this->page
            ])),
            '@type' => $this->totalPages > 1 ? 'hydra:PartialCollectionView' : 'hydra:CollectionView',
            'hydra:first' => $this->urlGenerator->generate($this->route, array_merge($this->currentParams, [
                '_format' => 'json',
                'page' => 1
            ])),
            'hydra:last' => $this->urlGenerator->generate($this->route, array_merge($this->currentParams, [
                '_format' => 'json',
                'page' => $this->totalPages
            ])),
            'hydra:previous' => $this->page > 1 ? $this->urlGenerator->generate($this->route, array_merge($this->currentParams, [
                '_format' => 'json',
                'page' => $this->page - 1
            ])) : null,
            'hydra:next' => $this->page < $this->totalPages ? $this->urlGenerator->generate($this->route, array_merge($this->currentParams, [
                '_format' => 'json',
                'page' => $this->page + 1
            ])) : null,
        ];
    }
}
