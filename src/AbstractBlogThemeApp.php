<?php

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
    const VERSION = '1.7.0';
    const ITEM_PER_PAGE = 15;

    protected static $themeName = 'Blog Theme';
    protected static $themeAuthor = 'REZO ZERO';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'AbstractBlogTheme';
    protected static $backendTheme = false;

    /**
     * {@inheritdoc}
     */
    public static $priority = 5;

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
