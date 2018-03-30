# Events API Theme
### Based on Théâtre de la Ville website.

**Events API aware middleware for your Roadiz theme.**

This a base theme, **you must extend it** and not directly register it in your Roadiz website.

```php
class MyThemeApp extends EventsApiThemeApp
{
    # Override theme methods
}
```

## Dependency injection

This theme adds back-office features and new Solr index and documents. Add its commands and services providers 
into your `app/conf/config.yml` file.

```yaml
additionalServiceProviders: 
    - \Themes\EventsApiTheme\Services\EventsApiServiceProvider
    
additionalCommands:
    - \Themes\EventsApiTheme\Command\ApiReindexCommand
```

Some services will be named after `tdv` as they’ve been created for their first time in *Théâtre de la Ville* project.

- `tdv_client`
- `tdv_api_endpoint`
- `tdv_api_user`
- `tdv_api_pass`

`EventsApiServiceProvider` will add twig templates, translations, event-dispatcher and all API providers to be used
in your API aware theme.

## Install with Composer

In order to install *Events API Theme* you must add custom repositories to your website `composer.json`.
These libraries are protected and you should have setup SSH private/public keys to download them. Make sure to configure
your CI/CD scripts to use the same SSH private/public keys to download outside of your development environment.

```json
{
    "require": {
        "rezozero/events-api-theme": "dev-master",
        "theatredelaville-paris/tdv-sdk": "dev-master"
    },
    "repositories": [
        {
            "type": "vcs",
            "url":  "git@gitlab.rezo-zero.com:rezo-zero-agency/EventsApiTheme.git"
        },
        {
            "type": "vcs",
            "url":  "git@gitlab.rezo-zero.com:theatredelaville-paris/tdv-sdk.git"
        }
    ]
}
```

## Install Settings

You can import `Resources/import/settings.rzt` file to populate your Events API credentials.

## Activate routing

Add Events API Theme routes to your custom theme `routes.yml` file:

```yaml
eventsApiRoutes:
    resource: "../../../vendor/rezozero/events-api-theme/src/Resources/routes.yml"
    prefix: /
    
routesDump:
   path:     /routing/routes.json
   methods:  ['GET']
   defaults:
       _controller: Themes\MyTheme\Controllers\RoutesController::dumpAction
```

## Enable offering platforms

For the moment, EventsApiTheme and Rezo Zero Events API support following ticketing systems:

- *Forum Sirius*
- *Rodrigue Thémis*

Override `offerPlatforms` service to add or remove platforms. Platform choice field will be displayed
only for more than 1 platform.

```php
$container['offerPlatforms'] = function ($c) {
    return [
        static::PLATFORM_FSIRIUS,
        static::PLATFORM_RODRIGUE_THEMIS,
    ];
};
```

### Required routes

Several routes are required for generating *Solr* index:

- `eventPageLocale` for `Event` detail page
- `placePageLocale` for `Place` detail page
- `artistPageLocale` for `Person` detail page
- `organizationPageLocale` for `Organization` detail page
- `newsFeedDetailsPageLocale` for `Article` detail page
- `specialOfferPageLocale` for `Offer` detail page

### Override Solarium subscriber

You can override your ApiItemDocument for Solr indexation by overriding `solariumApiSubscriber` service.
Be careful to override it **before** dispatcher is woken up.

### Publish theme assets

As EventsApiTheme cannot be registered as a *full legit* theme you’ll have to publish manually 
its web assets into your `web/` folder:

```shell
bin/roadiz themes:assets:install /Themes/EventsApiTheme/EventsApiThemeApp;
```

### Create front-end controllers

You can use *Traits* to implements your own front-end controllers using *EventsApiTheme* logic:

```php
<?php
namespace Themes\MyTheme\Controllers;

use Themes\EventsApiTheme\Controllers\CategoryControllerTrait;
use Themes\EventsApiTheme\Controllers\EventsApiControllerInterface;
use Themes\MyTheme\MyThemeApp;

class CategoryController extends MyThemeApp implements EventsApiControllerInterface
{
    use CategoryControllerTrait;
}
```
