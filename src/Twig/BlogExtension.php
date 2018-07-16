<?php

namespace Themes\AbstractBlogTheme\Twig;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use Twig\Extension\AbstractExtension;
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
            'get_latest_posts' => new TwigFunction($this, 'getLatestPosts'),
            'get_previous_post' => new TwigFunction($this, 'getPreviousPost'),
            'get_next_post' => new TwigFunction($this, 'getNextPost'),
            'get_latest_posts_for_tag' => new TwigFunction($this, 'getLatestPostsForTag'),
        ];
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
