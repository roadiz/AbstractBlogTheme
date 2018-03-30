# Abstract Blog Theme

**Abstract Blog middleware for your Roadiz theme.**

You must override these services:

- blog_theme.post_container_entity
- blog_theme.post_entity

with your own node-type class names.

```php
/**
 * @param Container $container
 */
public static function setupDependencyInjection(Container $container)
{
    parent::setupDependencyInjection($container);
    
    $container->extend('blog_theme.post_container_entity', function ($entityClass) {
        return NSBlogPostContainer::class;
    });
    
    $container->extend('blog_theme.post_entity', function ($entityClass) {
        return NSBlogPost::class;
    });
}
```
