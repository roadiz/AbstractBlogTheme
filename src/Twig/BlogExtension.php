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
            new TwigFunction('get_previous_post', [$this, 'getPreviousPost']),
            new TwigFunction('get_previous_post_for_tag', [$this, 'getPreviousPostForTag']),
            new TwigFunction('get_next_post', [$this, 'getNextPost']),
            new TwigFunction('get_next_post_for_tag', [$this, 'getNextPostForTag']),
            new TwigFunction('get_latest_posts', [$this, 'getLatestPosts']),
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
     *
     * @return string
     */
    public function getAmpifizedContent($content)
    {
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

        return $content;
    }

    /**
     * @param NodesSources $post
     *
     * @return array
     */
    private function getDefaultPrevNextCriteria(NodesSources $post)
    {
        return [
            'id' => ['!=', $post->getId()],
            'node.visible' => true,
            'translation' => $post->getTranslation(),
        ];
    }

    /**
     * @param NodesSources $post
     * @param int          $count
     * @param bool         $scopedToParent
     * @param array        $criteria
     *
     * @return null|NodesSources|array
     */
    public function getPreviousPost(NodesSources $post, $count = 1, $scopedToParent = false, array $criteria = [])
    {
        $criteria = array_merge(
            $this->getDefaultPrevNextCriteria($post),
            $criteria,
            [
                'publishedAt' => ['<', $post->getPublishedAt()],
            ]
        );
        if ($scopedToParent) {
            $criteria['node.parent'] = $post->getParent();
        }
        $posts = $this->entityManager->getRepository($this->postEntityClass)->findBy($criteria, [
            'publishedAt' => 'DESC'
        ], $count);

        if ($count === 1 && count($posts) > 0) {
            return $posts[0];
        } elseif ($count === 1 && count($posts) === 0) {
            return null;
        }

        return $posts;
    }

    /**
     * @param NodesSources $post
     * @param Tag          $tag
     * @param int          $count
     * @param bool         $scopedToParent
     *
     * @return null|NodesSources|array
     */
    public function getPreviousPostForTag(NodesSources $post, Tag $tag, $count = 1, $scopedToParent = false)
    {
        $criteria = array_merge($this->getDefaultPrevNextCriteria($post), [
            'publishedAt' => ['<', $post->getPublishedAt()],
            'tags' => $tag,
        ]);
        if ($scopedToParent) {
            $criteria['node.parent'] = $post->getParent();
        }
        $posts = $this->entityManager->getRepository($this->postEntityClass)->findBy($criteria, [
            'publishedAt' => 'DESC'
        ], $count);

        if ($count === 1 && count($posts) > 0) {
            return $posts[0];
        } elseif ($count === 1 && count($posts) === 0) {
            return null;
        }

        return $posts;
    }

    /**
     * @param NodesSources $post
     * @param int          $count
     * @param bool         $scopedToParent
     * @param array        $criteria
     *
     * @return null|NodesSources|array
     */
    public function getNextPost(NodesSources $post, $count = 1, $scopedToParent = false, array $criteria = [])
    {
        $criteria = array_merge(
            $this->getDefaultPrevNextCriteria($post),
            $criteria,
            [
                'publishedAt' => ['>', $post->getPublishedAt()],
            ]
        );
        if ($scopedToParent) {
            $criteria['node.parent'] = $post->getParent();
        }
        $posts = $this->entityManager->getRepository($this->postEntityClass)->findBy($criteria, [
            'publishedAt' => 'ASC'
        ], $count);

        if ($count === 1 && count($posts) > 0) {
            return $posts[0];
        } elseif ($count === 1 && count($posts) === 0) {
            return null;
        }

        return $posts;
    }

    /**
     * @param NodesSources $post
     * @param Tag          $tag
     * @param int          $count
     * @param bool         $scopedToParent
     *
     * @return null|NodesSources|array
     */
    public function getNextPostForTag(NodesSources $post, Tag $tag, $count = 1, $scopedToParent = false)
    {
        $criteria = array_merge($this->getDefaultPrevNextCriteria($post), [
            'publishedAt' => ['>', $post->getPublishedAt()],
            'tags' => $tag,
        ]);
        if ($scopedToParent) {
            $criteria['node.parent'] = $post->getParent();
        }
        $posts = $this->entityManager->getRepository($this->postEntityClass)->findBy($criteria, [
            'publishedAt' => 'ASC'
        ], $count);

        if ($count === 1 && count($posts) > 0) {
            return $posts[0];
        } elseif ($count === 1 && count($posts) === 0) {
            return null;
        }

        return $posts;
    }

    /**
     * @param Translation $translation
     * @param int $count
     * @param array $criteria
     *
     * @return array
     */
    public function getLatestPosts(Translation $translation, $count = 4, array $criteria = [])
    {
        $mandatoryCriteria = [
            'publishedAt' => ['<=', new \DateTime()],
            'node.visible' => true,
            'translation' => $translation,
        ];
        return $this->entityManager->getRepository($this->postEntityClass)->findBy(array_merge(
            $criteria,
            $mandatoryCriteria
        ), [
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
