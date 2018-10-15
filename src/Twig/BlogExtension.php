<?php

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
            new TwigFunction('get_latest_posts', [$this, 'getLatestPosts']),
            new TwigFunction('get_previous_post', [$this, 'getPreviousPost']),
            new TwigFunction('get_next_post', [$this, 'getNextPost']),
            new TwigFunction('get_latest_posts_for_tag', [$this, 'getLatestPostsForTag']),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('ampifize', [$this, 'getAmpifizedContent'], ['is_safe' => ['html']])
        ];
    }

    /**
     * @param string $content
     */
    public function getAmpifizedContent($content)
    {
        $content = strip_tags(
            $content,
            '<h1><h2><h3><h4><h5><h6><br><hr><table><th><tr><td><p><span><ol><ul><li><div><em><strong><iframe><blockquote><a>'
        );

        $replacements = [
            '<iframe' => '<amp-iframe layout="responsive" sandbox="allow-scripts allow-same-origin"',
            '</iframe' => '</amp-iframe',
            '<img' => '<amp-img layout="responsive"',
            '</img' => '</amp-img',
            'gesture="media"' => '',
            'frameborder="0"' => '',
            'style="text-align: justify;"' => ''
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        return $content;
    }

    /**
     * @param NodesSources $post
     *
     * @return null|object|NodesSources
     */
    public function getPreviousPost(NodesSources $post)
    {
        return $this->entityManager->getRepository($this->postEntityClass)->findOneBy([
            'id' => ['!=', $post->getId()],
            'publishedAt' => ['<', $post->getPublishedAt()],
            'node.visible' => true,
            'translation' => $post->getTranslation(),
        ], [
            'publishedAt' => 'DESC'
        ]);
    }

    /**
     * @param NodesSources $post
     *
     * @return null|object|NodesSources
     */
    public function getNextPost(NodesSources $post)
    {
        return $this->entityManager->getRepository($this->postEntityClass)->findOneBy([
            'id' => ['!=', $post->getId()],
            'publishedAt' => ['>', $post->getPublishedAt()],
            'node.visible' => true,
            'translation' => $post->getTranslation(),
        ], [
            'publishedAt' => 'ASC'
        ]);
    }

    /**
     * @param Translation $translation
     * @param int $count
     *
     * @return array
     */
    public function getLatestPosts(Translation $translation, $count = 4)
    {
        return $this->entityManager->getRepository($this->postEntityClass)->findBy([
            'publishedAt' => ['<=', new \DateTime()],
            'node.visible' => true,
            'translation' => $translation,
        ], [
            'publishedAt' => 'DESC'
        ], $count);
    }

    /**
     * @param Tag $tag
     * @param Translation $translation
     * @param int $count
     *
     * @return array
     */
    public function getLatestPostsForTag(Tag $tag, Translation $translation, $count = 4)
    {
        return $this->entityManager->getRepository($this->postEntityClass)->findBy([
            'publishedAt' => ['<=', new \DateTime()],
            'node.visible' => true,
            'translation' => $translation,
            'tags' => $tag,
        ], [
            'publishedAt' => 'DESC'
        ], $count);
    }
}
