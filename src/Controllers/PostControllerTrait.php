<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Controllers;

use JMS\Serializer\Serializer;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait PostControllerTrait
{
    use JsonLdSupportTrait;

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
            throw new \RuntimeException(
                'blog_theme.post_entity must be configured with your own BlogPost node-type class'
            );
        }

        /** @var Serializer $serializer */
        $serializer = $this->get('serializer');
        $ampArticle = $this->getJsonLdArticle($this->nodeSource);
        $this->assignation['jsonLdPost'] = $serializer->serialize($ampArticle, 'json');

        if ($this->allowAmpFormat() &&
           ($request->get('amp', 0) == 1 || $request->getRequestFormat('html') === 'amp')) {
            $response = $this->render($this->getAmpTemplate(), $this->assignation, null, '/');
        } elseif ($this->allowJsonFormat() &&
            ($request->get('json', 0) == 1 || $request->getRequestFormat('html') === 'json')) {
            $response = new Response(
                $this->assignation['jsonLdPost'],
                Response::HTTP_OK,
                ['content-type' => 'application/ld+json']
            );
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
     * @return string
     */
    public function getTemplate()
    {
        return 'pages/post.html.twig';
    }

    /**
     * @return string
     */
    public function getAmpTemplate()
    {
        return 'pages/post.amp.twig';
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
        return 5;
    }

    /**
     * @return bool
     */
    public function allowAmpFormat()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function allowJsonFormat()
    {
        return true;
    }
}
