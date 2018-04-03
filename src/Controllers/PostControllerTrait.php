<?php

namespace Themes\AbstractBlogTheme\Controllers;

use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait PostControllerTrait
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
    )
    {
        $this->prepareThemeAssignation($node, $translation);

        if ($this->get('blog_theme.post_entity') === false) {
            throw new \RuntimeException('blog_theme.post_entity must be configured with your own BlogPost node-type class');
        }

        return $this->render($this->getTemplate(), $this->assignation);
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return 'pages/post.html.twig';
    }

    /**
     * @return int
     */
    public function getItemsPerPage()
    {
        return 15;
    }
}
