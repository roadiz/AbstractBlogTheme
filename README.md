# Abstract Blog Theme

**Abstract Blog middleware for your Roadiz theme.**

## Dependency injection

Edit your `app/conf/config.yml` file to register additional blog theme services.

```yaml
additionalServiceProviders: 
    - \Themes\AbstractBlogTheme\Services\BlogServiceProvider

```

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

## Add node-types

Abstract Blog theme declare 3 node-types to create your blog website:

- `BlogFeedBlock`: to create an automatic feed preview on any page
- `BlogPost`: the main blog post entity
- `BlogPostContainer`: the blog container to host every blog post

```bash
bin/roadiz themes:install --data Themes/AbstractBlogTheme/AbstractBlogThemeApp
bin/roadiz generate:nsentities
bin/roadiz orm:schema-tool:update --dump-sql --force
```

## PostContainerTrait

`PostContainerTrait` will implement your `indexAction` by handling all request data
to provide your posts, filters and available tags to build your template.

IndexAction will assign:

- `posts`: found `NodesSources` array according to your criteria
- `filters`: pagination information array
- `tags`: available filtering `Tag` array
- `currentTag`: `Tag` or `null`
- `archives`: available *years* and *months* of post archives
- `currentArchive`: `string` or `not defined`
- `currentArchiveDateTime`: `\DateTime` or `not defined`

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

### Template examples

`Resources/views/` folder contains useful templates for creating your own blog. Feel free to include
them directly in your theme or duplicated them.

## Twig extension

- `get_latest_posts`
- `get_latest_posts_for_tag`
- `get_previous_post`
- `get_next_post`
