CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Handy cache tags module provides some handy extra cache tags.

 * For a full description of the module visit:
   https://www.drupal.org/project/handy_cache_tags

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/handy_cache_tags


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Handy cache tags module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

The module provides the following cache tags for you to use:

```
handy_cache_tags:[entity_type]
handy_cache_tags:[entity_type]:[entity_bundle]
```

For example with nodes of the bundle "article" this would make the following
cache tags available:

```
handy_cache_tags:node
handy_cache_tags:node:article
```

The first would invalidate on any node (which by coincidence also is provided
through the "node_list" cache tag in core). The second would invalidate on any
node being created, updated, or deleted with the bundle article.

To generate these tags for your render array, you can use the following:

```$build['#cache'] = [
  'tags' => [
    // This one generates the bundle specific cache tag.
    \Drupal::service('handy_cache_tags.manager')->getBundleTag('node', 'article'),
    // This one generates the entity type cache tag.
    \Drupal::service('handy_cache_tags.manager')->getTag('node'),
  ],
];
```

If your render array deals with an entity, you can also use the following helper
functions:

```
// Get all tags for an entity:
\Drupal::service('handy_cache_tags.manager')
  ->getEntityTags(EntityInterface $entity);

// Get entity type tag from an entity:
\Drupal::service('handy_cache_tags.manager')
  ->getEntityTypeTagFromEntity(EntityInterface $entity);

// Get bundle tag from entity:
\Drupal::service('handy_cache_tags.manager')
  ->getBundleTagFromEntity(EntityInterface $entity);
```


MAINTAINERS
-----------

 * Eirik Morland (eiriksm) - https://www.drupal.org/u/eiriksm

Supporting organization:

 * Ny Media AS - https://www.drupal.org/ny-media-as
