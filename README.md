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
- `currentTag`: `Tag`, `array<Tag>` or `null`
- `currentTagNames`: `array<string>` containing current filtering tag(s) name for your filter menu template.
- `archives`: available *years* and *months* of post archives
- `currentArchive`: `string` or `not defined`
- `currentArchiveDateTime`: `\DateTime` or `not defined`

### Filtering

You can filter your post-container entities using `Request` attributes or query params :

- `tag`: Filter by a tag’ name  using Roadiz nodes’s `tags` field. **You can pass an array of tag name to combine them.**
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
node-sources and its highlighted text. By default `SearchControllerTrait` intanciates a `Themes\AbstractBlogTheme\Model\SearchResult`
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
        $this->get('router')
    );
}
```

You’ll be able to add new virtual properties in your child `SearchResult` model such as:

```php
/**
 * Example property to display a blogpost excerpt already parsed
 * with Markdown syntax.
 *
 * @JMS\VirtualProperty()
 * @return string
 */
public function getExcerpt()
{
    if ($this->nodeSource instanceof NSBlogPost) {
        return \Parsedown::instance()->text($this->nodeSource->getExcerpt());
    }
    return null;
}
```

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

## Templates

`Resources/views/` folder contains useful templates for creating your own blog. Feel free to include
them directly in your theme or duplicated them.

By default, your Roadiz website will directly use *AbstractBlogTheme* templates. 
You can override them in your inheriting Theme using the exact same path and name.

## Twig extension
### Functions

- `get_latest_posts`
- `get_latest_posts_for_tag`
- `get_previous_post`
- `get_next_post`

### Filters
- `ampifize`: Strips unsupported tags in AMP format and convert `img` and `iframe` tags to their *AMP* equivalent.
