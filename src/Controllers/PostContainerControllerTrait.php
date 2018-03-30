<?php

namespace Themes\AbstractBlogTheme\Controllers;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\ListManagers\EntityListManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        /** @var EntityListManager $elm */
        $elm = $this->createEntityListManager(
            $this->get('blog_theme.post_entity'),
            $this->getDefaultCriteria($translation),
            $this->getDefaultOrder()
        );
        $elm->setItemPerPage($this->getItemsPerPage());
        $elm->handle();

        $this->assignation['posts'] = $elm->getEntities();
        $this->assignation['filters'] = $elm->getAssignation();

        return $this->render($this->getTemplate(), $this->assignation);
    }

    /**
     * @return array
     */
    protected function getDefaultCriteria(Translation $translation)
    {
        return [
            'node.visible' => true,
            'translation' => $translation,
            'publishedAt' => ['<=', new \DateTime()],
        ];
    }

    /**
     * @return array
     */
    protected function getDefaultOrder()
    {
        return [
            'publishedAt' => 'DESC'
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
