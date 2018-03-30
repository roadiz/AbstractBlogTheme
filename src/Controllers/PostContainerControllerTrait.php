<?php

namespace Themes\AbstractBlogTheme\Controllers;

use Doctrine\ORM\QueryBuilder;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

trait PostContainerControllerTrait
{
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

        if ($this->get('blog_theme.post_entity') === false) {
            throw new \RuntimeException('blog_theme.post_entity must be configured with your own BlogPost node-type class');
        }

        /** @var EntityListManager $elm */
        $elm = $this->createEntityListManager(
            $this->get('blog_theme.post_entity'),
            $this->getDefaultCriteria($translation, $request->query->get('tag'), $request->query->get('archive')),
            $this->getDefaultOrder()
        );
        $elm->setItemPerPage($this->getItemsPerPage());
        $elm->handle();

        $posts = $elm->getEntities();

        if (count($posts) === 0) {
            throw $this->createNotFoundException('No post found for given criteria.');
        }

        $this->assignation['posts'] = $posts;
        $this->assignation['currentTag'] = $this->getTag($request->query->get('tag'));
        $this->assignation['filters'] = $elm->getAssignation();
        $this->assignation['tags'] = $this->getAvailableTags();

        return $this->render($this->getTemplate(), $this->assignation);
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
        if ($tagName != '') {
            return $this->get('em')->getRepository(Tag::class)->findOneByTagName($tagName);
        }

        return null;
    }

    /**
     * @return array
     */
    protected function getDefaultCriteria(Translation $translation, $tagName = '', $archive = '')
    {
        $criteria = [
            'node.visible' => true,
            'translation' => $translation,
            $this->getPublicationField() => ['<=', new \DateTime()],
        ];

        if ($tagName != '') {
            $tag = $this->getTag($tagName);
            if (null === $tag) {
                throw $this->createNotFoundException('Tag does not exist.');
            }
            $criteria['tags'] = $tag;
        }

        if ($archive != '') {
            if (preg_match('#[0-9]{4}\-[0-9]{2}#', $archive) > 0) {
                $startDate = new \DateTime($archive . '-01 00:00:00');
                $endDate = clone $startDate;
                $endDate->add(new \DateInterval('P1M'));

                $criteria[$this->getPublicationField()] = ['BETWEEN', $startDate, $endDate];
            } elseif (preg_match('#[0-9]{4}#', $archive) > 0) {
                $startDate = new \DateTime($archive . '-01-01 00:00:00');
                $endDate = clone $startDate;
                $endDate->add(new \DateInterval('P1Y'));

                $criteria[$this->getPublicationField()] = ['BETWEEN', $startDate, $endDate];
            } else {
                throw $this->createNotFoundException('Archive filter is malformed.');
            }
        }

        return $criteria;
    }

    /**
     * @param Tag $parentTag Parent tag
     * @return array
     */
    protected function getAvailableTags(Tag $parentTag = null)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->get('em')
            ->getRepository(Tag::class)
            ->createQueryBuilder('t');

        /** @var QueryBuilder $subQb */
        $subQb = $this->get('em')
                      ->getRepository($this->get('blog_theme.post_entity'))
                      ->createQueryBuilder('p');

        try {
            $qb->select('t')
                ->innerJoin('t.nodes', 'n')
                ->innerJoin('n.nodeSources', 'ns')
                ->andWhere($qb->expr()->in('ns.id', $subQb->select('p.id')->getDQL()))
                ->andWhere($qb->expr()->eq('t.visible', true));

            if (null !== $parentTag) {
                $parentTagId = $parentTag->getId();
                $qb->innerJoin('t.parent', 'pt')
                    ->andWhere('pt.id = :parent')
                    ->setParameter('parent', $parentTagId);
            }

            $this->alterTagQueryOrderBy($qb);

            return $qb->getQuery()->getResult();
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
}
