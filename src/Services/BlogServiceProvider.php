<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Markdown\MarkdownInterface;
use RZ\SocialLinks\Twig\SocialLinksExtension;
use Symfony\Component\Translation\Translator;
use Themes\AbstractBlogTheme\Factory\JsonLdFactory;
use Themes\AbstractBlogTheme\Twig\BlogExtension;
use Twig\Loader\FilesystemLoader;

class BlogServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     * @return void
     */
    public function register(Container $container)
    {
        $container['jsonld.defaultImageOptions'] = function () {
            return [
                'width' => 800,
            ];
        };

        /**
         * @param Container $c
         *
         * TODO: Implement and override with your own JsonLdFactory
         *
         * @return JsonLdFactory
         */
        $container[JsonLdFactory::class] = function (Container $c) {
            return new JsonLdFactory(
                $c['document.url_generator'],
                $c['router'],
                $c['settingsBag'],
                $c['jsonld.defaultImageOptions'],
                $c[MarkdownInterface::class]
            );
        };

        $container->extend('twig.extensions', function ($extensions, Container $c) {
            if ($c->offsetExists('blog_theme.post_entity')) {
                // Add Blog extension only if post entity is registered.
                $extensions->add(new BlogExtension($c['em'], $c['blog_theme.post_entity']));
            }
            $extensions->add(new SocialLinksExtension());

            return $extensions;
        });

        $container->extend('twig.loaderFileSystem', function (FilesystemLoader $loader, $c) {
            $loader->prependPath(dirname(__DIR__) . '/Resources/views', 'AbstractBlogTheme');
            $loader->prependPath(dirname(__DIR__) . '/Resources/views');

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
