## [2.1.0 (2023-07-18)](https://github.com/roadiz/AbstractBlogTheme/compare/2.0.4...2.1.0)


### ⚠ BREAKING CHANGES

* `PostContainerControllerTrait::$availableTags`, `$countPerAvailableTags` and `$archives` are now `LazyTraversable` instance to be used directly in your Twig templates. Do not use `array` methods on them.

### Features

* New `LazyTraversable` class to lazy load tags, counts and archive in PostContainerControllerTrait ([b9b3847](https://github.com/roadiz/AbstractBlogTheme/commit/b9b38478ed929a17cb6b0a450f69edb4ad19cc94))

## 2.0.4 (2022-11-09)

### Bug Fixes

* Use EntityListManager getItemCount instead of Paginator count ([4092e99](https://github.com/roadiz/AbstractBlogTheme/commit/4092e99fdfb45cf7b42b0b9e5ce8a7151516de9c))

## 2.0.3 (2022-11-09)

### Bug Fixes

* Do not allow sorting and searching upon request ([71320e7](https://github.com/roadiz/AbstractBlogTheme/commit/71320e778ed054f21ca6ee09a22f16de4858dc31))

