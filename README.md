# Abstract Blog Theme

**Abstract Blog middleware for your Roadiz theme.**

- [Inheritance](#inheritance)
- [Dependency injection](#dependency-injection)
- [Add node-types](#add-node-types)
- [PostContainerControllerTrait](#postcontainercontrollertrait)
  * [Filtering](#filtering)
  * [Usage](#usage)
    + [Override PostContainerControllerTrait behaviour](#override-postcontainercontrollertrait-behaviour)
- [PostControllerTrait](#postcontrollertrait)
  * [Usage](#usage-1)
    + [Override PostControllerTrait behaviour](#override-postcontrollertrait-behaviour)
- [Search engine with Solr](#search-engine-with-solr)
    + [Override PostControllerTrait behaviour](#override-postcontrollertrait-behaviour-1)
  * [Search result model](#search-result-model)
- [AMP mobile page support](#amp-mobile-page-support)
- [Templates](#templates)
- [Twig extension](#twig-extension)
  * [Functions](#functions)
  * [Filters](#filters)

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

Edit your own `app/AppKernel.php` to register Blog services:

```php
use Themes\AbstractBlogTheme\Services\BlogServiceProvider;

/**
 * {@inheritdoc}
 */
public function register(\Pimple\Container $container)
{
    parent::register($container);
    $container->register(new BlogServiceProvider());
}
```

You must override these services in your custom theme:

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

## PostContainerControllerTrait

`PostContainerControllerTrait` will implement your `indexAction` by handling all request data
to provide your posts, filters and available tags to build your template.

IndexAction will assign:

- `posts`: found `NodesSources` array according to your criteria
- `filters`: pagination information array
- `tags`: available filtering `Tag` array
- `currentTag`: `Tag`, `array<Tag>` or `null`
- `currentTagNames`: `array<string>` containing current filtering tag(s) name for your filter menu template.
- `currentRelationSource`: `NodesSources` or `null` containing the filtering related entity
- `currentRelationsSources`: `array<NodesSources>` containing current filtering related entities(s) for your filter menu template.
- `currentRelationsNames`: `array<string>` containing current filtering related entities(s) name for your filter menu template.
- `archives`: available *years* and *months* of post archives
- `currentArchive`: `string` or `not defined`
- `currentArchiveDateTime`: `\DateTime` or `not defined`

### Filtering

You can filter your post-container entities using `Request` attributes or query params :

- `tag`: Filter by a tag’ name  using Roadiz nodes’s `tags` field. **You can pass an array of tag name to combine them.**
- `archive`: Filter by month and year, or just year on `publishedAt` field, or the one defined by `getPublicationField` method.
- `related`: Filter by a related node’ name using Roadiz nodes’s `bNodes` field. **You can pass an array of node name to combine them.**

### Usage

All you need to do is creating your `PostContainer` node-source's `Controller` in your theme and 
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

#### Multiple container controllers usage

If you have more than one blog-post type (`Blogpost` and `PressReview` for example), we advise strongly to create 
an *Abstract* class in your theme using this *Trait* before using it, it will ease up
method overriding if you have multiple container controllers classes:

```php
<?php
namespace Themes\MyTheme\Controllers;

use Themes\AbstractBlogTheme\Controllers\ConfigurableController;
use Themes\AbstractBlogTheme\Controllers\PostContainerControllerTrait;
use Themes\MyTheme\MyThemeThemeApp;

abstract class AbstractContainerController extends MyThemeThemeApp implements ConfigurableController
{
    use PostContainerControllerTrait;

    // common methods overriding here…
}
```

Then, simply inherit from your *Abstract* in your multiple container controller definitions:

```php
<?php
namespace Themes\MyTheme\Controllers;

class BlogPostContainerController extends AbstractContainerController
{
    // override whatever you want
}

class PressReviewContainerController extends AbstractContainerController
{
    // override whatever you want
}
```

#### Override PostContainerControllerTrait behaviour

Those methods can be overridden to customize your `PostContainerControllerTrait` behaviour. 

- `getTemplate`: By default it returns `pages/post-container.html.twig`. It will search in every registered themes for this template and fallback on `@AbstractBlogTheme/pages/post-container.html.twig`. Make sure your own theme have a higher priority.
- `getRssTemplate`: By default it returns `pages/post-container.rss.twig`. It will search in every registered themes for this template and fallback on `@AbstractBlogTheme/pages/post-container.rss.twig`. Make sure your own theme have a higher priority.
- `throwExceptionOnEmptyResult`: By default it returns `true`. It throws a 404 when no posts found. 
- `getPostEntity`: By default it returns `$this->get('blog_theme.post_entity')` as classname string. You can customize it to list other nodes.
- `isScopedToCurrentContainer`: By default it returns `false`, `PostContainerControllerTrait` will fetch **all** blog-post no matter where
there are. If your overriden `isScopedToCurrentContainer` method returns `true`, all blog post will be fetched only from your 
current container allowing you to create many blog containers.
- `isTagExclusive`: **returns** true by default, match posts linked with all tags exclusively (intersection). Override it to `false` if you want to match posts with *any* tags (union).
- `getPublicationField`: By default this method returns `publishedAt` field name. You can return whatever name unless field
exists in your BlogPost node-type.
- `getDefaultCriteria`: **returns** default post query criteria. We encourage you to override `getCriteria` instead to keep default tags, archives and related filtering system.
- `getCriteria`: Override default post query criteria, this method must return an array.
- `getDefaultOrder`: By default this method returns an array :
```php
[
    $this->getPublicationField() => 'DESC'
]
```
- `getResponseTtl`: By default this method returns `5` (minutes).
- `selectPostCounts`: By default `false`: make additional queries to get each tag’ post count to display posts count number in your tags menu. 
- `prepareListingAssignation`: This is the critical method which performs all queries and tag resolutions. **We do not recommend overriding this method**, override other methods to change your PostContainer behaviour instead.
- `getRelatedNodesSourcesQueryBuilder`: if you want to fetch only one type related node-sources. Or filter more precisely.

You can override other methods, just get a look at the `PostContainerControllerTrait` file…

## PostControllerTrait

`PostControllerTrait` will implement your `indexAction` by handling all request data
to provide a single post with its multiple formats.

### Usage

All you need to do is creating your `Post` node-source'`Controller` in your theme and 
implements `ConfigurableController` and use `PostControllerTrait`.

```php
<?php
namespace Themes\MyTheme\Controllers;

use Themes\AbstractBlogTheme\Controllers\ConfigurableController;
use Themes\AbstractBlogTheme\Controllers\PostControllerTrait;
use Themes\MyTheme\MyThemeThemeApp;

class BlogPostController extends MyThemeThemeApp implements ConfigurableController
{
    use PostControllerTrait;
}
```

#### Override PostControllerTrait behaviour

Those methods can be overridden to customize your `PostControllerTrait` behaviour.

- `getJsonLdArticle`: By default it returns a new `JsonLdArticle` to be serialized to JSON or AMP friendly format. 
- `getTemplate`: By default it returns `pages/post.html.twig`. It will search in every registered themes for this template and fallback on `@AbstractBlogTheme/pages/post.html.twig`. Make sure your own theme have a higher priority.
- `getAmpTemplate`: By default it returns `pages/post.amp.twig`. It will search in every registered themes for this template and fallback on `@AbstractBlogTheme/pages/post.amp.twig`. Make sure your own theme have a higher priority.
- `allowAmpFormat`: By default it returns `true`.
- `allowJsonFormat`: By default it returns `true`.
- `getResponseTtl`: By default this method returns `5` (minutes).


## Search engine with Solr

```php
<?php
namespace Themes\MyTheme\Controllers;

use Themes\AbstractBlogTheme\Controllers\ConfigurableController;
use Themes\AbstractBlogTheme\Controllers\SearchControllerTrait;
use Themes\MyTheme\MyThemeApp;

class SearchController extends MyThemeApp implements ConfigurableController
{
    use SearchControllerTrait;
}
```

```yaml
searchPageLocale:
    path: /{_locale}/search.{_format}/{page}
    defaults:
        _controller: Themes\MyTheme\Controllers\SearchController::searchAction
        _locale: en
        page: 1
        _format: html
    requirements:
        # Use every 2 letter codes (quick and dirty)
        _locale: "en|fr"
        page: "[0-9]+"
        _format: html|json
```

Add your search form in your website templates (use GET method to enable user history):

```twig
<form method="get" action="{{ path('searchPageLocale', {
    '_locale': request.locale
}) }}" data-json-action="{{ path('searchPageLocale', {
    '_locale': request.locale,
    '_format': 'json',
}) }}" id="search">
    <input type="search" name="q">
    <button type="submit">{% trans %}search{% endtrans %}</button>
</form>
```

Then create `pages/search.html.twig` template.

#### Override PostControllerTrait behaviour

- `getTemplate()`: string
- `getAmpTemplate()`: string
- `getJsonLdArticle()`: JsonLdArticle
- `getResponseTtl()`: string
- `allowAmpFormat()`: boolean
- `allowJsonFormat()`: boolean

### Search result model

For JSON search responses, `SearchControllerTrait` uses JMS Serializer with a custom model to decorate your
node-sources and its highlighted text. By default `SearchControllerTrait` instantiates a `Themes\AbstractBlogTheme\Model\SearchResult`
object that will be serialized. You can override this model if you want to add custom fields according to your 
node-sources data.

Create a child class, then override `createSearchResultModel` method:

```php
/**
 * @param $searchResult
 *
 * @return SearchResult
 */
protected function createSearchResultModel($searchResult)
{
    return new SearchResult(
        $searchResult['nodeSource'],
        $searchResult['highlighting'],
        $this->get('document.url_generator'),
        $this->get('router'),
        $this->get('translator')
    );
}
```

You’ll be able to add new virtual properties in your child `SearchResult` model.

## AMP mobile page support

[AMP](https://www.ampproject.org) format is supported for blog-post detail pages. 

- Disable `display_debug_panel` setting
- Add `?amp=1` after your blog-post detail page Url. Or add `?amp=1#development=1` for dev mode.
- Add amp `link` to your HTML template:

```twig
{% block share_metas %}
    {{ parent() }}
    <link rel="amphtml" href="{{ url(nodeSource, {'amp': 1}) }}">
{% endblock %}
```

## RSS feed support

RSS format is supported for blog-post **containers** listing pages.

- Add RSS `link` into your HTML template:

```twig
{% block share_metas %}
    {{ parent() }}
    <link rel="alternate" href="{{ url(nodeSource, {
            '_format': 'xml',
            'page': filters.currentPage,
            'tag': currentTag.tagName,
            'archive': currentArchive
        }) }}" title="{{ pageMeta.title }}" type="application/rss+xml">
{% endblock %}
```

## Templates

`Resources/views/` folder contains useful templates for creating your own blog. Feel free to include
them directly in your theme or duplicated them.

By default, your Roadiz website will directly use *AbstractBlogTheme* templates. 
You can override them in your inheriting Theme using the exact same path and name.

## Twig extension

### Functions

- `get_latest_posts($translation, $count = 4)`
- `get_latest_posts_for_tag($tag, $translation, $count = 4)`
- `get_previous_post($nodeSource, $count = 1, $scopedToParent = false)`: Get previous post(s) sorted by `publishedAt`.   
*Returns a single `NodesSource` by default, returns an `array` if count > 1.*
- `get_previous_post_for_tag($nodeSource, $tag, $count = 1, $scopedToParent = false)`: Get previous post(s) sorted by `publishedAt` and filtered by one `Tag`.   
*Returns a single `NodesSource` by default, returns an `array` if count > 1.*
- `get_next_post($nodeSource, $count = 1, $scopedToParent = false)`: Get next post(s) sorted by `publishedAt`.    
*Returns a single NodesSource by default, returns an array if count > 1.*
- `get_next_post_for_tag($nodeSource, $tag, $count = 1, $scopedToParent = false)`: Get next post(s) sorted by `publishedAt` and filtered by one `Tag`.   
*Returns a single `NodesSource` by default, returns an `array` if count > 1.*

### Filters
- `ampifize`: Strips unsupported tags in AMP format and convert `img` and `iframe` tags to their *AMP* equivalent.
