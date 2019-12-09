<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Controllers;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializationContext;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Themes\AbstractBlogTheme\Exception\FilteringEntityNotFound;
use Themes\AbstractBlogTheme\Factory\JsonLdFactory;
use Themes\AbstractBlogTheme\Model\HydraCollection;
use Twig\Error\RuntimeError;

trait PostContainerControllerTrait
{
    use JsonLdSupportTrait;

    protected static $availableSortFields = [
        'title',
        'publishedAt'
    ];

    /**
     * @var Tag[]
     */
    protected $availableTags;

    /**
     * @var array
     */
    protected $countPerAvailableTags;

    /**
     * @var array
     */
    protected $archives;

    /**
     * @return bool
     */
    protected function throwExceptionOnEmptyResult()
    {
        return false;
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
     * Makes all data assignations:
     * - nodes
     * - tags
     * - archives
     * And filter all these against current Request.
     *
     * @param Request $request
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function prepareListingAssignation(Request $request): void
    {
        if ($this->getPostEntity() === false) {
            throw new \RuntimeException(
                'blog_theme.post_entity must be configured with your own BlogPost node-type class'
            );
        }

        if (null === $this->translation) {
            throw new BadRequestHttpException('Translation cannot be found');
        }

        $this->availableTags = $this->getAvailableTags($this->translation);
        /*
         * When you want to display post count numbers on each available tags.
         */
        if ($this->selectPostCounts()) {
            $this->countPerAvailableTags = $this->getPostCountForTags($this->availableTags, $this->translation);
            $this->assignation['postsCountForTagId'] = $this->countPerAvailableTags;
        }
        $this->archives = $this->getArchives($this->translation);

        /**
         * @var EntityListManager $elm
         */
        try {
            $elm = $this->createEntityListManager(
                $this->getPostEntity(),
                array_merge(
                    $this->getDefaultCriteria(
                        $this->translation,
                        $request
                    ),
                    $this->getCriteria(
                        $this->translation,
                        $request
                    )
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
        } catch (FilteringEntityNotFound $entityNotFound) {
            $this->assignation['posts'] = [];
            $this->assignation['filters'] = [];
        }

        $this->assignation['tags'] = $this->availableTags;
        $this->assignation['archives'] = $this->archives;
        $this->assignation['sorts'] = static::$availableSortFields;
    }

    /**
     * @param Request          $request
     * @param Node|null        $node
     * @param Translation|null $translation
     *
     * @return Response
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws RuntimeError
     */
    public function indexAction(
        Request $request,
        Node $node = null,
        Translation $translation = null
    ) {
        $this->prepareThemeAssignation($node, $translation);
        /*
         * Makes all data assignations:
         * - nodes
         * - tags
         * - archives
         * And filter all these against current Request
         */
        $this->prepareListingAssignation($request);

        $_format = $request->get('_format', 'html');

        if ($_format === 'json') {
            $response = $this->renderHydra($this->assignation);
        } elseif ($_format === 'xml' || $_format === 'rss') {
            $response = $this->renderRss($this->getRssTemplate(), $this->assignation, null, '/');
        } else {
            $response = $this->render($this->getTemplate(), $this->assignation, null, '/');
        }

        $response->headers->add([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Request-Headers' => '*'
        ]);

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
     * @param array $parameters
     *
     * @return Response
     */
    public function renderHydra(array $parameters = []): Response
    {
        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');

        return new JsonResponse(
            $serializer->serialize(
                $this->getHydraCollection($parameters),
                'json',
                SerializationContext::create()
                    ->enableMaxDepthChecks()
            ),
            Response::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @param array $parameters
     *
     * @return HydraCollection
     */
    protected function getHydraCollection(array $parameters = []): HydraCollection
    {
        $articles = [];
        /** @var NodesSources $post */
        foreach ($parameters['posts'] as $post) {
            $articles[] = $this->getJsonLdArticle($post);
        }

        /** @var Request $request */
        $request = $this->get('requestStack')->getMasterRequest();
        return $this->get(JsonLdFactory::class)->createHydraCollection(
            $articles,
            $parameters['filters']['itemCount'],
            $parameters['filters']['currentPage'],
            $parameters['filters']['pageCount'],
            $this->nodeSource ?: $request->attributes->get('_route'),
            $request->attributes->get('_route_params')
        );
    }

    /**
     * Return a Response from a template string with its rendering assignation.
     *
     * @see http://api.symfony.com/2.6/Symfony/Bundle/FrameworkBundle/Controller/Controller.html#method_render
     *
     * @param string   $view       Template file path
     * @param array    $parameters Twig assignation array
     * @param Response $response   Optional Response object to customize response parameters
     * @param string   $namespace  Twig loader namespace
     *
     * @return Response
     * @throws RuntimeError
     */
    public function renderRss($view, array $parameters = [], Response $response = null, $namespace = "")
    {
        if (!$this->get('stopwatch')->isStarted('twigRender')) {
            $this->get('stopwatch')->start('twigRender');
        }

        try {
            if (null === $response) {
                $response = new Response(
                    '',
                    Response::HTTP_OK,
                    ['Content-Type' => 'application/xml; charset=UTF-8']
                );
            }
            $response->setContent($this->renderView($this->getNamespacedView($view, $namespace), $parameters));

            return $response;
        } catch (RuntimeError $e) {
            if ($e->getPrevious() instanceof ForceResponseException) {
                return $e->getPrevious()->getResponse();
            } else {
                throw $e;
            }
        }
    }

    /**
     * @param array<Tag>  $tags
     * @param Translation $translation
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function getPostCountForTags(array $tags, Translation $translation): array
    {
        $counts = [];
        /** @var Tag $tag */
        foreach ($tags as $tag) {
            $counts[$tag->getId()] = $this->getPostCountForTag($tag, $translation);
        }
        return $counts;
    }

    /**
     * @param Tag         $tag
     * @param Translation $translation
     *
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPostCountForTag(Tag $tag, Translation $translation): int
    {
        /**
         * @var QueryBuilder $qb
         */
        $qb = $this->getPostRepository()->createQueryBuilder('p');
        $qb->select($qb->expr()->countDistinct('p'))
            ->innerJoin('p.node', 'n')
            ->innerJoin('n.tags', 't')
            ->andWhere($qb->expr()->eq('t', ':tag'))
            ->setParameter(':tag', $tag)
            ->setCacheable(true)
        ;

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

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Translation $translation
     * @param Request     $request
     *
     * @return array
     */
    protected function getCriteria(Translation $translation, Request $request)
    {
        return [];
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
            return $this->get('em')->getRepository(Tag::class)->findOneBy(
                [
                'tagName' => $tagName,
                'translation' => $this->translation,
                ]
            );
        }

        return null;
    }

    /**
     * @param string $nodeName
     *
     * @return Node|null
     */
    protected function findNodeByName($nodeName = '')
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
     * @throws \Exception|FilteringEntityNotFound
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
                $tags = array_map(
                    function (string $name) {
                        $tag = $this->getTag($name);
                        if (null === $tag) {
                            throw new FilteringEntityNotFound('Tag does not exist.');
                        }
                        return $tag;
                    },
                    $tagName
                );
                $criteria['tags'] = $tags;
                $criteria['tagExclusive'] = $this->isTagExclusive();
                $this->assignation['currentTag'] = $tags;
                $this->assignation['currentTagNames'] = array_map(
                    function (Tag $tag) {
                        return $tag->getTagName();
                    },
                    $tags
                );
            } else {
                $tag = $this->getTag($tagName);
                if (null === $tag) {
                    throw new FilteringEntityNotFound('Tag does not exist.');
                }
                $criteria['tags'] = $tag;
                $criteria['tagExclusive'] = $this->isTagExclusive();
                $this->assignation['currentTag'] = $tag;
                $this->assignation['currentTagNames'] = [$tag->getTagName()];
            }
        } else {
            $this->assignation['currentTagNames'] = [];
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
            }
        } else {
            $this->assignation['currentArchive'] = null;
        }

        /*
         * Support filtering by related node entity.
         */
        if ('' != $related = $request->get('related', '')) {
            if (is_array($related)) {
                $relatedNodes = array_map(
                    function (string $name) {
                        $relatedNode = $this->findNodeByName($name);
                        if (null === $relatedNode) {
                            throw new FilteringEntityNotFound('Node does not exist.');
                        }
                        return $relatedNode;
                    },
                    $related
                );
                $this->assignation['currentRelations'] = $relatedNodes;
                $this->assignation['currentRelationsSources'] = array_map(function (Node $node) {
                    return $node->getNodeSources()->first();
                }, $relatedNodes);
                $this->assignation['currentRelationsNames'] = array_map(function (Node $node) {
                    return $node->getNodeName();
                }, $relatedNodes);
                ;
                /*
                 * Use bNode from NodesToNodes without field specification.
                 */
                $criteria['node.bNodes.nodeB'] = $relatedNodes;
            } else {
                if (null !== $relatedNode = $this->findNodeByName($related)) {
                    $this->assignation['currentRelation'] = $relatedNode;
                    $this->assignation['currentRelations'] = [$relatedNode];
                    $this->assignation['currentRelationsNames'] = [$relatedNode->getNodeName()];
                    $this->assignation['currentRelationSource'] = $relatedNode->getNodeSources()->first();
                    $this->assignation['currentRelationsSources'] = [$relatedNode->getNodeSources()->first()];

                    /*
                     * Use bNode from NodesToNodes without field specification.
                     */
                    $criteria['node.bNodes.nodeB'] = $relatedNode;
                } else {
                    $this->assignation['currentRelation'] = null;
                    $this->assignation['currentRelationSource'] = null;
                    $this->assignation['currentRelations'] = [];
                    $this->assignation['currentRelationsSources'] = [];
                    $this->assignation['currentRelationsNames'] = [];
                }
            }
        } else {
            $this->assignation['currentRelation'] = null;
            $this->assignation['currentRelationSource'] = null;
            $this->assignation['currentRelations'] = [];
            $this->assignation['currentRelationsSources'] = [];
            $this->assignation['currentRelationsNames'] = [];
        }

        if ($this->isScopedToCurrentContainer()) {
            $criteria['node.parent'] = $this->node;
        }

        return $criteria;
    }

    /**
     * @param Translation $translation
     * @param Tag         $parentTag   Parent tag
     *
     * @return array
     */
    protected function getAvailableTags(Translation $translation, Tag $parentTag = null)
    {
        /**
         * @var QueryBuilder $qb
         */
        $qb = $this->get('em')
            ->getRepository(Tag::class)
            ->createQueryBuilder('t');

        /**
         * @var QueryBuilder $subQb
         */
        $subQb = $this->getPostRepository()->createQueryBuilder('p');

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
    }

    /**
     * @param Translation $translation
     *
     * @return NodesSources[]
     */
    protected function getAvailableRelatedNodesSources(Translation $translation): array
    {
        /**
         * @var QueryBuilder $qb
         */
        $qb = $this->getRelatedNodesSourcesQueryBuilder();

        /**
         * @var QueryBuilder $subQb
         */
        $subQb = $this->getPostRepository()->createQueryBuilder('p');

        $qb->select('ns, n')
            ->innerJoin('ns.node', 'n')
            ->leftJoin('n.aNodes', 'an')
            ->leftJoin('an.nodeA', 'nodeA')
            ->andWhere($qb->expr()->in('nodeA.id', $subQb->select('pn.id')->innerJoin('p.node', 'pn')->getDQL()))
            ->andWhere($qb->expr()->eq('n.visible', true))
            ->andWhere($qb->expr()->eq('ns.translation', ':translation'))
            ->addOrderBy('ns.title', 'ASC')
            ->setParameter(':translation', $translation);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return QueryBuilder
     */
    protected function getRelatedNodesSourcesQueryBuilder(): QueryBuilder
    {
        return $this->get('em')
            ->getRepository(NodesSources::class)
            ->createQueryBuilder('ns');
    }

    /**
     * Return all post values for given field.
     *
     * @param Translation $translation
     * @param string      $prefixedFieldName DQL field (prefix with p. for post source, n. for post node or t. for post
     *     translation)
     * @param string      $sorting           ASC or DESC
     *
     * @return array
     */
    protected function getAvailableValuesForField(Translation $translation, $prefixedFieldName, $sorting = 'ASC')
    {
        /**
         * @var QueryBuilder $qb
         */
        $qb = $this->getPostRepository()->createQueryBuilder('p');

        $qb->select($prefixedFieldName)
            ->innerJoin('p.node', 'n')
            ->innerJoin('p.translation', 't')
            ->addOrderBy($prefixedFieldName, $sorting)
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
        /** @var Request $request */
        $request = $this->get('requestStack')->getCurrentRequest();
        $sort = $request->get('sort', null);
        $sortDirection = 'ASC';
        $requestSortDirection = $request->get('sortDirection', null);
        if (null !== $requestSortDirection && in_array($requestSortDirection, ['ASC', 'DESC'])) {
            $sortDirection = $requestSortDirection;
        }

        if (null !== $sort && in_array($sort, static::$availableSortFields)) {
            $this->assignation['currentSort'] = $sort;
            $this->assignation['currentSortDirection'] = $sortDirection;
            return [
                $sort => $sortDirection
            ];
        }

        $this->assignation['currentSort'] = $this->getPublicationField();
        $this->assignation['currentSortDirection'] = 'DESC';

        return [
            $this->getPublicationField() => 'DESC',
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
     * @throws \Exception
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
            ->setParameters(
                [
                'translation' => $translation,
                'datetime' => new \Datetime('now'),
                ]
            );
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
     * @throws \Exception
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
     * @return string
     */
    public function getRssTemplate()
    {
        return 'pages/post-container.rss.twig';
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
        if (null !== $this->nodeSource) {
            return $this->nodeSource->getNode()->getTtl();
        }
        return 2;
    }

    /**
     * @return bool Get results matching all chosen tags. And not any.
     */
    protected function isTagExclusive()
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function selectPostCounts(): bool
    {
        return false;
    }
}
