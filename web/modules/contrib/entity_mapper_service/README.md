# Entity Mapper Service

The entity mapper service defines a pattern in which 
content entities can be mapped into associative arrays
in a hierarchical manner.

1) Perform transformations that apply to all entity types.
2) Add in transformations that are entity type specific (node, media).
3) Add in transformations that are bundle specific (a youtube media bundle).

This pattern replaces d6/d7 style array building hooks with 
more structured, easier to override behaviors. As with d6/d7 style
hooks, at each subsequent step in the process, the transformation can
act on both the entity and results of previous transformation steps.

# Scenario

You want your share links (facebook, twitter, etc) to 
include google analytics tags, but the rules around 
populating these tags are complicated. 

Some apply to all entities.

```
utm_medium = "user generated content"
```

Some apply to particule bundles.

```
utm_content = $entity->field_analytics_code->value
```

# Actual Usage

First, you define a service which uses the EntityMapperService
as it's class.

sharer.services.yml

```
services:
  sharer.mapper:
    class: Drupal\entity_mapper_service\EntityMapperService
    arguments: ['sharer']
```

At the same time you may also define a generic entity mapper
as well as some entity type specific mappers.

```
  sharer.entity_mapper:
    class: Drupal\sharer\EntityMapService
  sharer.node_mapper:
    class: Drupal\sharer\NodeMapService
  sharer.media_mapper:    
    class: Drupal\sharer\MediaMapService
```

As you build bundles, you may define bundle specific mappers.

```
  sharer.media_youtube_mapper:
    class: Drupal\my_youtube_feature\YoutubeMapperService
```

Regardless of the module in which the service is defined,
always name them like so.

```
{service_group}.entity_mapper
{service_group}.{entity_type}_mapper
{service_group}.{entity_type}_{bundle_id}_mapper
```

# For Distribution Building

The original use case for this module was in complex distribution 
building. We ourselves maintain multiple flavors of a distribution
while aiming to make our code adoptable by others (on campus).

By breaking out mappings by entity, entity_type, and bundle services,
we 

* make everything overrideable.
* make those overrides granular.
* make code easier to package discretely.
* provide a consistent pattern for mapping in the distribution family.

## Empty Mappers

In a multi-team distribution building context, you might want to establish a 
pattern in which every bundle includes a mapper regardless of whether
or not it needs to do anything special. This way, teams extending 
your code do not need to worry about whether or not a service is 
already defined (or might be in the future). Instead, the standard procedure
is always to alter a service.

If you'd like to define a service that efficiently does nothing, use the
EmptyMapperService class included in this module.
