<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Twig;

use Doctrine\Persistence\ManagerRegistry;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class BlogExtension extends AbstractExtension
{
    use PublishableItemExtension;

    private ManagerRegistry $managerRegistry;
    /**
     * @var class-string
     */
    private string $postEntityClass;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param class-string $postEntityClass
     */
    public function __construct(ManagerRegistry $managerRegistry, string $postEntityClass)
    {
        $this->postEntityClass = $postEntityClass;
        $this->managerRegistry = $managerRegistry;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('get_previous_post', [$this, 'getPreviousItem']),
            new TwigFunction('get_previous_post_for_tag', [$this, 'getPreviousItemForTag']),
            new TwigFunction('get_next_post', [$this, 'getNextItem']),
            new TwigFunction('get_next_post_for_tag', [$this, 'getNextItemForTag']),
            new TwigFunction('get_latest_posts', [$this, 'getLatestItems']),
            new TwigFunction('get_latest_posts_for_tag', [$this, 'getLatestItemsForTag']),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('ampifize', [$this, 'getAmpifizedContent'], ['is_safe' => ['html']])
        ];
    }

    /**
     * @return class-string
     */
    protected function getEntity(): string
    {
        return $this->postEntityClass;
    }

    /**
     * @return ManagerRegistry
     */
    public function getManagerRegistry(): ManagerRegistry
    {
        return $this->managerRegistry;
    }

    /**
     * @param string|null $content
     *
     * @return string|null
     */
    public function getAmpifizedContent($content)
    {
        if (null !== $content) {
            $content = strip_tags(
                $content,
                '<h1><h2><h3><h4><h5><h6><br><hr><table><th><tr><td><p><span><ol><ul><li><div><em><strong><iframe><blockquote><a>'
            );

            $replacements = [
                '<iframe' => '<amp-iframe layout="responsive" sandbox="allow-scripts allow-same-origin allow-presentation"',
                '</iframe' => '</amp-iframe',
                '<img' => '<amp-img layout="responsive"',
                '</img' => '</amp-img',
                'gesture="media"' => '',
                'frameborder="0"' => '',
                'style="text-align: justify;"' => ''
            ];

            $content = str_replace(array_keys($replacements), array_values($replacements), $content);
        }

        return $content;
    }
}
