<?php
/**
 * Copyright (c) 2018. Rezo Zero
 *
 * This file is part of Events API Theme.
 *
 * Events API Theme can not be copied and/or distributed without
 * the express permission of Rezo Zero.
 *
 * @file AbstractBlogThemeApp.php
 * @author Ambroise Maupate <ambroise@rezo-zero.com>
 * @copyright Rezo Zero 2018
 */
namespace Themes\AbstractBlogTheme;

use Pimple\Container;
use RZ\Roadiz\CMS\Controllers\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AbstractBlogThemeApp class
 */
class AbstractBlogThemeApp extends FrontendController
{
    const VERSION = '1.0.0';
    const ITEM_PER_PAGE = 15;

    protected static $themeName = 'Blog Theme';
    protected static $themeAuthor = 'REZO ZERO';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'AbstractBlogTheme';
    protected static $backendTheme = false;

    /**
     * @param Request $request
     * @param Response $response
     * @param int $minutes TTL in minutes
     *
     * @return Response
     */
    public function makeResponseCachable(Request $request, Response $response, $minutes)
    {
        $kernel = $this->get('kernel');
        if (!$kernel->isPreview() && !$kernel->isDebug() && $request->isMethodCacheable()) {
            $response->setPublic();
            $response->setMaxAge(60 * $minutes);
            $response->setSharedMaxAge(60 * $minutes);
            $response->setVary('Accept-Encoding, X-Partial, x-requested-with');
            if ($request->isXmlHttpRequest()) {
                $response->headers->add([
                    'X-Partial' => true
                ]);
            }
        }

        return $response;
    }

    /**
     * @param Container $container
     */
    public static function setupDependencyInjection(Container $container)
    {
        parent::setupDependencyInjection($container);

        $container['blog_theme.post_container_entity'] = function () {
            return false;
        };

        $container['blog_theme.post_entity'] = function () {
            return false;
        };
    }
}