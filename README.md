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

## PostContainerTrait

`PostContainerTrait` will implement your `indexAction` by handling all request data
to provide your posts, filters and available tags to build your template.

IndexAction will assign:

- `posts`: found `NodesSources` array according to your criteria
- `currentTag`: tag or `null`
- `filters`: pagination information array
- `tags`: available filtering `Tag` array

### Usage

All you need to do is creating your node-source `Controller` in your theme and 
implements `ConfigurableController` and use `PostContainerControllerTrait`.
You will be able to override any methods to configure your blog listing.


```php
<?php
namespace Themes\MyTheme\Controllers;

use Themes\AbstractBlogTheme\Controllers\ConfigurableController;
use Themes\AbstractBlogTheme\Controllers\PostContainerControllerTrait;
use Themes\MyTheme\MyThemeThemeApp;

class BlogPostContainerController extends MyThemeThemeApp implements ConfigurableController
{
    use PostContainerControllerTrait;
}
```
