<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Twig;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;

trait PublishableItemExtension
{
    abstract protected function getEntity(): string;
    abstract protected function getEntityManager(): EntityManagerInterface;

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

    protected function getItems(array $criteria, int $count, string $direction = 'ASC'): array
    {
        return $this->getEntityManager()->getRepository($this->getEntity())->findBy($criteria, [
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
    public function getPreviousItem(NodesSources $item, $count = 1, $scopedToParent = false, array $criteria = [])
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
    public function getPreviousItemForTag(NodesSources $item, Tag $tag, $count = 1, $scopedToParent = false)
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
    public function getNextItem(NodesSources $item, $count = 1, $scopedToParent = false, array $criteria = [])
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
    public function getNextItemForTag(NodesSources $item, Tag $tag, $count = 1, $scopedToParent = false)
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
     * @param Translation $translation
     * @param int         $count
     * @param array       $criteria
     *
     * @return array
     * @throws \Exception
     */
    public function getLatestItems(Translation $translation, $count = 4, array $criteria = [])
    {
        $criteria = array_merge($criteria, [
            'publishedAt' => ['<=', new \DateTime()],
            'node.visible' => true,
            'translation' => $translation,
        ]);
        return $this->getItems($criteria, $count, 'DESC');
    }

    /**
     * @param Tag         $tag
     * @param Translation $translation
     * @param int         $count
     *
     * @return array
     * @throws \Exception
     */
    public function getLatestItemsForTag(Tag $tag, Translation $translation, $count = 4, array $criteria = [])
    {
        $criteria = array_merge($criteria, [
            'publishedAt' => ['<=', new \DateTime()],
            'node.visible' => true,
            'translation' => $translation,
            'tags' => $tag,
        ]);
        return $this->getItems($criteria, $count, 'DESC');
    }
}
