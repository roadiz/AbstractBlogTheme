# Abstract Blog Theme

**Abstract Blog middleware for your Roadiz theme.**

## Inheritance

Your own theme entry class must **extend** `AbstractBlogThemeApp` instead of `FrontendController` to provide essential methods:

```php
use Themes\AbstractBlogTheme\AbstractBlogThemeApp;

class MyThemeApp extends AbstractBlogThemeApp
{
    //…
}
```

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

### Filtering

You can filter your post-container entities using `Request` attributes or query params :

- `tag`: Filter by a tag’ name  using Roadiz nodes’s `tags` field
- `archive`: Filter by month and year, or just year on `publishedAt` field, or the one defined by `getPublicationField` method.
- `related`: Filter by a related node’ name using Roadiz nodes’s `bNodes` field

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

#### Override PostContainerControllerTrait behaviour

Those methods can be overriden to customize your `PostContainerControllerTrait` behaviour.

- `throwExceptionOnEmptyResult`: By default it returns `true`. It throws a 404 when no posts found. 
- `getPostEntity`: By default it returns `$this->get('blog_theme.post_entity')` as classname string. You can customize it to list other nodes.
- `isScopedToCurrentContainer`: By default it returns `false`, `PostContainerControllerTrait` will fetch **all** blog-post no matter where
there are. If your overriden `isScopedToCurrentContainer` method returns `true`, all blog post will be fetched only from your 
current container allowing you to create many blog containers.
- `getPublicationField`: By default this method returns `publishedAt` field name. You can return whatever name unless field
exists in your BlogPost node-type.
- `getDefaultOrder`: By default this method returns an array :
```php
[
    $this->getPublicationField() => 'DESC'
]
```

### Template

`Resources/views/` folder contains useful templates for creating your own blog. Feel free to include
them directly in your theme or duplicated them.

By default, your Roadiz website will directly use *AbstractBlogTheme* templates. 
You can override them in your inheriting Theme using the exact same path and name.

## Twig extension

- `get_latest_posts`
- `get_latest_posts_for_tag`
- `get_previous_post`
- `get_next_post`
