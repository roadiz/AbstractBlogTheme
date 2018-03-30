<?php

namespace Themes\AbstractBlogTheme\Controllers;

use Doctrine\ORM\EntityRepository;
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
        $this->assignation['tags'] = $this->getAvailableTags($translation);
        $this->assignation['archives'] = $this->getArchives($translation);

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

        return $criteria;
    }

    /**
     * @param Tag $parentTag Parent tag
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
     * @return EntityRepository
     */
    protected function getPostRepository()
    {
        return $this->get('em')->getRepository($this->get('blog_theme.post_entity'));
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
            ->andWhere($qb->expr()->eq('p.translation', ':translation'))
            ->andWhere($qb->expr()->lte($publicationField, ':datetime'))
            ->addGroupBy($publicationField)
            ->orderBy($publicationField, 'DESC')
            ->setParameters([
                'translation' => $translation,
                'datetime' => new \Datetime('now'),
            ])
        ;

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
        $years = [];
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
}
