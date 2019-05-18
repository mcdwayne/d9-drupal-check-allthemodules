# LCache

[![CircleCI](https://circleci.com/gh/lcache/drupal-8/tree/8.x-1.x.svg?style=svg)](https://circleci.com/gh/lcache/drupal-8/tree/8.x-1.x)


This module provides a combination L1/L2 cache using a combination
of APCu as L1 with a database as L2 (and for coherency management
among the L1 caches).

Currently only supported on Pantheon, but there's nothing that
inherently relies on anything Pantheon-specific.

## Composer

Composer is the best way to install this module because this module relies on [the LCache Library](https://github.com/lcache/lcache).

```
composer config repositories.drupal composer https://packages.drupal.org/8
```

Next, require this module.

```
composer require drupal/lcache
```

## Usage

First, enable LCache module. This will make LCache available. Next chose which cache bins you would like to be served from LCache. If you wish to use LCache as the default cache for on your site, add this line to your `settings.php` file.

```php
$settings['cache']['default'] = 'cache.backend.lcache';
```

LCache is most beneficial for read-heavy caches. You may want to only use LCache on individual cache bins. You can do so again in `settings.php` like this:

```php
$settings['cache']['bins']['render'] = 'cache.backend.lcache';
```

## Feedback and collaboration

Bug reports, feature requests, and feedback should be posted [in the drupal.org issue queue](https://www.drupal.org/project/issues/lcache). For code changes, please submit pull requests against [the GitHub repository](https://github.com/lcache/drupal-8) rather than posting patches to drupal.org.