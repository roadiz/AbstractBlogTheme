<?php

namespace Themes\AbstractBlogTheme\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Themes\AbstractBlogTheme\Twig\BlogExtension;
use Symfony\Component\Translation\Translator;

class BlogServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container->extend('twig.extensions', function ($extensions, $c) {
            $extensions->add(new BlogExtension($c['em'], $c['blog_theme.post_entity']));
            return $extensions;
        });

        $container->extend('twig.loaderFileSystem', function (\Twig_Loader_Filesystem $loader, $c) {
            $loader->addPath(dirname(__DIR__) . '/Resources/views', 'AbstractBlogTheme');

            return $loader;
        });

        $container->extend('translator', function (Translator $translator, $c) {
            $translator->addResource(
                'xlf',
                dirname(__DIR__) . '/Resources/translations/messages.en.xlf',
                'en'
            );
            $translator->addResource(
                'xlf',
                dirname(__DIR__) . '/Resources/translations/messages.fr.xlf',
                'fr'
            );
            $translator->addResource(
                'xlf',
                dirname(__DIR__) . '/Resources/translations/validators.en.xlf',
                'en',
                'validators'
            );
            $translator->addResource(
                'xlf',
                dirname(__DIR__) . '/Resources/translations/validators.fr.xlf',
                'fr',
                'validators'
            );
            return $translator;
        });

    }
}