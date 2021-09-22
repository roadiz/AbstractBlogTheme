<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme;

use Pimple\Container;
use RZ\Roadiz\CMS\Controllers\FrontendController;

class AbstractBlogThemeApp extends FrontendController
{
    const VERSION = '2.0.0';
    const ITEM_PER_PAGE = 15;

    protected static string $themeName = 'Blog Theme';
    protected static string $themeAuthor = 'REZO ZERO';
    protected static string $themeCopyright = 'REZO ZERO';
    protected static string $themeDir = 'AbstractBlogTheme';
    protected static bool $backendTheme = false;

    /**
     * {@inheritdoc}
     */
    public static int $priority = 5;

    /**
     * @param Container $container
     * @return void
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
