<?php

namespace Themes\AbstractBlogTheme\Services;

use GeneratedNodeSources\NSBlogPost;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Entities\NodesSources;
use Themes\AbstractBlogTheme\Twig\BlogExtension;
use Symfony\Component\Translation\Translator;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use JMS\Serializer\EventDispatcher\EventDispatcher;
use Doctrine\Common\Annotations\AnnotationRegistry;

class BlogServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['searchResults.nodesSourcesSerializerHandler'] = function($c) {
            return function ($visitor, NodesSources $obj, array $type) {
                return [
                    'id' => $obj->getId(),
                    'locale' => $obj->getTranslation()->getLocale(),
                    'title' => $obj->getTitle(),
                    'node' => [
                        'id' => $obj->getNode()->getId(),
                        'nodeName' => $obj->getNode()->getNodeName(),
                    ],
                    'publishedAt' => $obj->getPublishedAt(),
                ];
            };
        };

        $container['searchResults.serializer'] = function ($c) {
            \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');
            $serializer = SerializerBuilder::create()
                ->setCacheDir($c['kernel']->getCacheDir())
                ->setDebug($c['kernel']->isDebug())
                ->setPropertyNamingStrategy(
                    new \JMS\Serializer\Naming\SerializedNameAnnotationStrategy(
                        new \JMS\Serializer\Naming\IdenticalPropertyNamingStrategy()
                    )
                )
                ->addDefaultHandlers()
                ->configureListeners(function(EventDispatcher $dispatcher) {
                    $dispatcher->addListener('serializer.pre_serialize',
                        function (PreSerializeEvent $event) {
                            if ($event->getObject() instanceof NodesSources){
                                $event->setType(NodesSources::class);
                            }
                        }
                    );
                })
                ->configureHandlers(function(HandlerRegistry $registry) use ($c) {
                    $registry->registerHandler(
                        'serialization',
                        NodesSources::class,
                        'json',
                        $c['searchResults.nodesSourcesSerializerHandler']
                    );
                })
                ->build();

            return $serializer;
        };

        $container->extend('twig.extensions', function ($extensions, $c) {
            $extensions->add(new BlogExtension($c['em'], $c['blog_theme.post_entity']));
            $extensions->add(new \RZ\SocialLinks\Twig\SocialLinksExtension());

            return $extensions;
        });

        $container->extend('twig.loaderFileSystem', function (\Twig_Loader_Filesystem $loader, $c) {
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
