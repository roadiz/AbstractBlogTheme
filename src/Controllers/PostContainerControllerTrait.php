<?php

namespace Themes\AbstractBlogTheme\Controllers;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait PostContainerControllerTrait
{
    /**
     * @return bool
     */
    protected function throwExceptionOnEmptyResult()
    {
        return true;
    }
    /**
     * Override this method if you want to fetch blog-posts only
     * from current post-container.
     *
     * @return bool
     */
    protected function isScopedToCurrentContainer()
    {
        return false;
    }

    /**
     * @return string|boolean
     */
    protected function getPostEntity()
    {
        return $this->get('blog_theme.post_entity');
    }

    /**
     * @param Request $request
     * @param Node|null $node
     * @param Translation|null $translation
     * @return Response
     */
    public function indexAction(
        Request $request,
        Node $node = null,
        Translation $translation = null
    ) {
        $this->prepareThemeAssignation($node, $translation);

        if ($this->getPostEntity() === false) {
            throw new \RuntimeException('blog_theme.post_entity must be configured with your own BlogPost node-type class');
        }

        /** @var EntityListManager $elm */
        $elm = $this->createEntityListManager(
            $this->getPostEntity(),
            $this->getDefaultCriteria(
                $translation,
                $request
            ),
            $this->getDefaultOrder()
        );
        $elm->setItemPerPage($this->getItemsPerPage());
        $elm->handle();
        $elm->setPage($request->get('page', 1));

        $posts = $elm->getEntities();

        if (count($posts) === 0 && $this->throwExceptionOnEmptyResult()) {
            throw $this->createNotFoundException('No post found for given criteria.');
        }

        $this->assignation['posts'] = $posts;
        $this->assignation['filters'] = $elm->getAssignation();
        $this->assignation['tags'] = $this->getAvailableTags($translation);
        $this->assignation['archives'] = $this->getArchives($translation);

        $response = $this->render($this->getTemplate(), $this->assignation, null, '/');

        if ($this->getResponseTtl() > 0) {
            /*
             * Set http cache for current request
             * only if prod mode.
             *
             * Be careful! Do not use cache
             * if page contains form and user content!
             */
            return $this->makeResponseCachable($request, $response, $this->getResponseTtl());
        }

        return $response;
    }

    /**
     * @return string
     */
    protected function getPublicationField()
    {
        return 'publishedAt';
    }

    /**
     * @param string $tagName
     *
     * @return Tag|null
     */
    protected function getTag($tagName = '')
    {
        if ($tagName !== '') {
            return $this->get('em')->getRepository(Tag::class)->findOneBy([
                'tagName' => $tagName,
                'translation' => $this->translation,
            ]);
        }

        return null;
    }

    /**
     * @param string $nodeName
     *
     * @return Node|null
     */
    protected function getNode($nodeName = '')
    {
        if ($nodeName !== '') {
            return $this->get('nodeApi')->getOneBy([
                'nodeName' => $nodeName,
                'translation' => $this->translation,
            ]);
        }

        return null;
    }

    /**
     * @param Translation $translation
     * @param Request     $request
     *
     * @return array
     */
    protected function getDefaultCriteria(Translation $translation, Request $request)
    {
        $criteria = [
            'node.visible' => true,
            'translation' => $translation,
            $this->getPublicationField() => ['<=', new \DateTime()],
        ];

        if ('' != $tagName = $request->get('tag', '')) {
            if (is_array($tagName)) {
                $tags = array_map(function ($name) {
                    $tag = $this->getTag($name);
                    if (null === $tag) {
                        throw $this->createNotFoundException('Tag does not exist.');
                    }
                    return $tag;
                }, $tagName);
                $criteria['tags'] = $tags;
                $criteria['tagExclusive'] = true;
                $this->assignation['currentTag'] = $tags;
                $this->assignation['currentTagNames'] = array_map(function (Tag $tag) {
                    return $tag->getTagName();
                }, $tags);
            } else {
                $tag = $this->getTag($tagName);
                if (null === $tag) {
                    throw $this->createNotFoundException('Tag does not exist.');
                }
                $criteria['tags'] = $tag;
                $criteria['tagExclusive'] = true;
                $this->assignation['currentTag'] = $tag;
                $this->assignation['currentTagNames'] = [$tag->getTagName()];
            }
        }

        if ('' != $archive = $request->get('archive', '')) {
            if (preg_match('#[0-9]{4}\-[0-9]{2}#', $archive) > 0) {
                $startDate = new \DateTime($archive . '-01 00:00:00');
                $endDate = clone $startDate;
                $endDate->add(new \DateInterval('P1M'));

                $criteria[$this->getPublicationField()] = ['BETWEEN', $startDate, $endDate];
                $this->assignation['currentArchive'] = $archive;
                $this->assignation['currentArchiveDateTime'] = $startDate;
            } elseif (preg_match('#[0-9]{4}#', $archive) > 0) {
                $startDate = new \DateTime($archive . '-01-01 00:00:00');
                $endDate = clone $startDate;
                $endDate->add(new \DateInterval('P1Y'));

                $criteria[$this->getPublicationField()] = ['BETWEEN', $startDate, $endDate];
                $this->assignation['currentArchive'] = $archive;
                $this->assignation['currentArchiveDateTime'] = $startDate;
            } else {
                throw $this->createNotFoundException('Archive filter is malformed.');
            }
        }

        if ('' != $related = $request->get('related', '')) {
            if (null !== $relatedNode = $this->getNode($related)) {
                $this->assignation['currentRelation'] = $relatedNode;
                $this->assignation['currentRelationSource'] = $relatedNode->getNodeSources()->first();

                /*
                 * Use bNode from NodesToNodes without field specification.
                 */
                $criteria['node.bNodes.nodeB'] = $relatedNode;
            }
        }

        if ($this->isScopedToCurrentContainer()) {
            $criteria['node.parent'] = $this->node;
        }

        return $criteria;
    }

    /**
     * @param Translation $translation
     * @param Tag $parentTag Parent tag
     *
     * @return array
     */
    protected function getAvailableTags(Translation $translation, Tag $parentTag = null)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->get('em')
            ->getRepository(Tag::class)
            ->createQueryBuilder('t');

        /** @var QueryBuilder $subQb */
        $subQb = $this->getPostRepository()->createQueryBuilder('p');

        try {
            $qb->select('t')
                ->leftJoin('t.translatedTags', 'tt')
                ->innerJoin('t.nodes', 'n')
                ->innerJoin('n.nodeSources', 'ns')
                ->andWhere($qb->expr()->in('ns.id', $subQb->select('p.id')->getDQL()))
                ->andWhere($qb->expr()->eq('t.visible', true))
                ->andWhere($qb->expr()->eq('tt.translation', ':translation'))
                ->setParameter(':translation', $translation);

            if (null !== $parentTag) {
                $parentTagId = $parentTag->getId();
                $qb->innerJoin('t.parent', 'pt')
                    ->andWhere('pt.id = :parent')
                    ->setParameter('parent', $parentTagId);
            }

            $this->alterTagQueryOrderBy($qb);
            /*
             * Enforce tags nodes status not to display Tags which are linked to draft posts.
             */
            if ($this->get('kernel')->isPreview()) {
                $qb->andWhere($qb->expr()->lte('n.status', Node::PUBLISHED));
            } else {
                $qb->andWhere($qb->expr()->eq('n.status', Node::PUBLISHED));
            }

            if ($this->isScopedToCurrentContainer()) {
                $qb->andWhere($qb->expr()->eq('n.parent', ':parentNode'))
                    ->setParameter(':parentNode', $this->node);
            }

            return $qb->getQuery()->getResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * Return all post values for given field.
     *
     * @param Translation $translation
     * @param string $prefixedFieldName DQL field (prefix with p. for post source, n. for post node or t. for post translation)
     *
     * @return array
     */
    protected function getAvailableValuesForField(Translation $translation, $prefixedFieldName)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getPostRepository()->createQueryBuilder('p');

        try {
            $qb->select($prefixedFieldName)
                ->innerJoin('p.node', 'n')
                ->innerJoin('p.translation', 't')
                ->andWhere($qb->expr()->eq('n.visible', true))
                ->andWhere($qb->expr()->eq('p.translation', ':translation'))
                ->setParameter(':translation', $translation);
            /*
             * Enforce tags nodes status not to display Tags which are linked to draft posts.
             */
            if ($this->get('kernel')->isPreview()) {
                $qb->andWhere($qb->expr()->lte('n.status', Node::PUBLISHED));
            } else {
                $qb->andWhere($qb->expr()->eq('n.status', Node::PUBLISHED));
            }

            if ($this->isScopedToCurrentContainer()) {
                $qb->andWhere($qb->expr()->eq('n.parent', ':parentNode'))
                    ->setParameter(':parentNode', $this->node);
            }

            return array_filter(array_map('current', $qb->getQuery()->getArrayResult()));
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return QueryBuilder
     */
    protected function alterTagQueryOrderBy(QueryBuilder $queryBuilder)
    {
        return $queryBuilder->addOrderBy('t.position', 'ASC');
    }

    /**
     * @return array
     */
    protected function getDefaultOrder()
    {
        return [
            $this->getPublicationField() => 'DESC'
        ];
    }

    /**
     * @return EntityRepository
     */
    protected function getPostRepository()
    {
        return $this->get('em')->getRepository($this->getPostEntity());
    }

    /**
     * @param Translation $translation
     *
     * @return array
     */
    protected function getPostPublicationDates(Translation $translation)
    {
        $qb = $this->getPostRepository()->createQueryBuilder('p');
        $publicationField = 'p.' . $this->getPublicationField();

        $qb->select($publicationField)
            ->innerJoin('p.node', 'n')
            ->andWhere($qb->expr()->eq('p.translation', ':translation'))
            ->andWhere($qb->expr()->lte($publicationField, ':datetime'))
            ->addGroupBy($publicationField)
            ->orderBy($publicationField, 'DESC')
            ->setParameters([
                'translation' => $translation,
                'datetime' => new \Datetime('now'),
            ])
        ;
        /*
         * Enforce post nodes status not to display Archives which are linked to draft posts.
         */
        if ($this->get('kernel')->isPreview()) {
            $qb->andWhere($qb->expr()->lte('n.status', Node::PUBLISHED));
        } else {
            $qb->andWhere($qb->expr()->eq('n.status', Node::PUBLISHED));
        }

        if ($this->isScopedToCurrentContainer()) {
            $qb->andWhere($qb->expr()->eq('n.parent', ':parentNode'))
                ->setParameter(':parentNode', $this->node);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Translation $translation
     *
     * @return array
     */
    protected function getArchives(Translation $translation)
    {
        $array = [];
        $datetimes = $this->getPostPublicationDates($translation);

        foreach ($datetimes as $datetime) {
            $year = $datetime[$this->getPublicationField()]->format('Y');
            $month = $datetime[$this->getPublicationField()]->format('Y-m');

            if (!isset($array[$year])) {
                $array[$year] = [];
            }
            if (!isset($array[$month])) {
                $array[$year][$month] = new \DateTime($datetime[$this->getPublicationField()]->format('Y-m-01'));
            }
        }

        return $array;
    }


    /**
     * @return string
     */
    public function getTemplate()
    {
        return 'pages/post-container.html.twig';
    }

    /**
     * @return int
     */
    public function getItemsPerPage()
    {
        return 15;
    }

    /**
     * @return int
     */
    public function getResponseTtl()
    {
        return 2;
    }
}
