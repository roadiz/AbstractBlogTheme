<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Twig;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class BlogExtension extends AbstractExtension
{
    use PublishableItemExtension;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var string
     */
    private $postEntityClass;

    /**
     * BlogExtension constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param string $postEntityClass
     */
    public function __construct(EntityManagerInterface $entityManager, $postEntityClass)
    {
        $this->entityManager = $entityManager;
        $this->postEntityClass = $postEntityClass;
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

    protected function getEntity(): string
    {
        return $this->postEntityClass;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
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
