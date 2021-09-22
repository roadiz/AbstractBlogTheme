<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Twig;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;

trait PublishableItemExtension
{
    /**
     * @return class-string
     */
    abstract protected function getEntity(): string;

    /**
     * @return ManagerRegistry
     */
    abstract protected function getManagerRegistry(): ManagerRegistry;

    /**
     * @param NodesSources $item
     *
     * @return array
     */
    private function getDefaultPrevNextCriteria(NodesSources $item)
    {
        return [
            'id' => ['!=', $item->getId()],
            'node.visible' => true,
            'translation' => $item->getTranslation(),
        ];
    }

    /**
     * @param array $criteria
     * @param int $count
     * @param string $direction
     * @return array<NodesSources>
     */
    protected function getItems(array $criteria, int $count, string $direction = 'ASC'): array
    {
        /** @var EntityRepository<NodesSources> $repository */
        $repository = $this->getManagerRegistry()->getRepository($this->getEntity());
        return $repository->findBy($criteria, [
            'publishedAt' => $direction
        ], $count);
    }

    /**
     * @param NodesSources $item
     * @param int          $count
     * @param bool         $scopedToParent
     * @param array        $criteria
     *
     * @return null|NodesSources|array
     */
    public function getPreviousItem(NodesSources $item, int $count = 1, bool $scopedToParent = false, array $criteria = [])
    {
        $criteria = array_merge(
            $this->getDefaultPrevNextCriteria($item),
            $criteria,
            [
                'publishedAt' => ['<', $item->getPublishedAt()],
            ]
        );
        if ($scopedToParent) {
            $criteria['node.parent'] = $item->getParent();
        }
        $items = $this->getItems($criteria, $count, 'DESC');

        if ($count === 1 && count($items) > 0) {
            return $items[0];
        } elseif ($count === 1 && count($items) === 0) {
            return null;
        }

        return $items;
    }

    /**
     * @param NodesSources $item
     * @param Tag          $tag
     * @param int          $count
     * @param bool         $scopedToParent
     *
     * @return null|NodesSources|array
     */
    public function getPreviousItemForTag(NodesSources $item, Tag $tag, int $count = 1, bool $scopedToParent = false)
    {
        $criteria = array_merge($this->getDefaultPrevNextCriteria($item), [
            'publishedAt' => ['<', $item->getPublishedAt()],
            'tags' => $tag,
        ]);
        if ($scopedToParent) {
            $criteria['node.parent'] = $item->getParent();
        }
        $items = $this->getItems($criteria, $count, 'DESC');
        ;

        if ($count === 1 && count($items) > 0) {
            return $items[0];
        } elseif ($count === 1 && count($items) === 0) {
            return null;
        }

        return $items;
    }

    /**
     * @param NodesSources $item
     * @param int          $count
     * @param bool         $scopedToParent
     * @param array        $criteria
     *
     * @return null|NodesSources|array
     */
    public function getNextItem(NodesSources $item, int $count = 1, bool $scopedToParent = false, array $criteria = [])
    {
        $criteria = array_merge(
            $this->getDefaultPrevNextCriteria($item),
            $criteria,
            [
                'publishedAt' => ['>', $item->getPublishedAt()],
            ]
        );
        if ($scopedToParent) {
            $criteria['node.parent'] = $item->getParent();
        }
        $items = $this->getItems($criteria, $count, 'ASC');

        if ($count === 1 && count($items) > 0) {
            return $items[0];
        } elseif ($count === 1 && count($items) === 0) {
            return null;
        }

        return $items;
    }

    /**
     * @param NodesSources $item
     * @param Tag          $tag
     * @param int          $count
     * @param bool         $scopedToParent
     *
     * @return null|NodesSources|array
     */
    public function getNextItemForTag(NodesSources $item, Tag $tag, int $count = 1, bool $scopedToParent = false)
    {
        $criteria = array_merge($this->getDefaultPrevNextCriteria($item), [
            'publishedAt' => ['>', $item->getPublishedAt()],
            'tags' => $tag,
        ]);
        if ($scopedToParent) {
            $criteria['node.parent'] = $item->getParent();
        }
        $items = $this->getItems($criteria, $count, 'ASC');

        if ($count === 1 && count($items) > 0) {
            return $items[0];
        } elseif ($count === 1 && count($items) === 0) {
            return null;
        }

        return $items;
    }

    /**
     * @param TranslationInterface $translation
     * @param int         $count
     * @param array       $criteria
     *
     * @return array
     * @throws \Exception
     */
    public function getLatestItems(TranslationInterface $translation, int $count = 4, array $criteria = [])
    {
        $criteria = array_merge($criteria, [
            'publishedAt' => ['<=', new \DateTime()],
            'node.visible' => true,
            'translation' => $translation,
        ]);
        return $this->getItems($criteria, $count, 'DESC');
    }

    /**
     * @param Tag $tag
     * @param TranslationInterface $translation
     * @param int $count
     * @param array $criteria
     * @return array
     */
    public function getLatestItemsForTag(
        Tag $tag,
        TranslationInterface $translation,
        int $count = 4,
        array $criteria = []
    ) {
        $criteria = array_merge($criteria, [
            'publishedAt' => ['<=', new \DateTime()],
            'node.visible' => true,
            'translation' => $translation,
            'tags' => $tag,
        ]);
        return $this->getItems($criteria, $count, 'DESC');
    }
}
