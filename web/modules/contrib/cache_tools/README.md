Cache tools

Description:
------------
Module introduces couple of improvements to default Drupal cache behavior.

Features:
---------

### 1. Sanitizing cache tags.

Module allows to sanitize cache tags and contexts for listed blocks.
It's using custom BlockViewBuilder to sanitize tags and contexts of blocks.
By default it's stripping several contexts (route, url, url.query_args)
and couple of tags (node_list, taxonomy_term_list). You can also decide
which additional tags and contexts you would like to strip. Everything
is configured in cache_tools.services.yml under parameter section.
You can override parameters by introducing custom services.yml

### 2. Custom cache tag for views.

Module introduces also custom cache tag for the views. It's optimizing
to place more precise tags for the views. Namely, it places
`{entity}_{bundle}_pub` (like `node_article_pub` or `node_recipe_pub`)
instead of too general `node_list`. By doing so it will handle invalidation
of published nodes only respecting the bundle of the node. This tag is placed
out-of-the-box based on the view configuration. Module is auto extracting
the filter and arguments handler settings for particular view. If there is
such handler it will auto identify entity and bundle and set\
`{entity}_{bundle}_pub` tag. Invalidation is handled automatically during
entity_insert and entity_update events.

### 2. Custom cache tags based on field values.

Field-based cache tags of the following format 
`entitytype_entitybundle_pub:field_name:value` can be configured, e.g.:
```yml
    invalidate:
      node:
        - article:field_author
```
will produce the cache tag `node_article_pub:field_author:123` where 123 is
the value of the field_author field (author id).

Invalidation is handled automatically during entity operations:
1. Insert published: all non-empty field values.
2. Delete published: all non-empty field values.
3. Update published: only the modified field values.
4. Update and publish: all non-empty field values.
5. Update and unpublish: all non-empty original field values.

Use case for this:
A block or view listing nodes filtered by bundle and a given field value. We
want the cache to be invalidated only when nodes having that bundle and field
value are added, deleted or modified. E.g.
1. You have author/user entity
2. On the author/user page you'll see list of content of this author/user
3. List (or view) should be invalidated only when this author/user is assigned
to some new published content. In case of updating the published content,
both previous and current author/user page should be invalidated.
4. Placing tags like `node_article_pub:field_author:{author_id}` is excellent
way to deal with the problem. Every time the new content is added which contain
this author, `node_article_pub:field_author:123` is invalidated so only page
of author/user 123 will be invalidated.
