<?php

namespace Themes\AbstractBlogTheme\Twig;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;

class BlogExtension extends \Twig_Extension
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
            'get_latest_posts' => new \Twig_Function_Method($this, 'getLatestPosts'),
            'get_latest_posts_for_tag' => new \Twig_Function_Method($this, 'getLatestPostsForTag'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'blog';
    }

    /**
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
