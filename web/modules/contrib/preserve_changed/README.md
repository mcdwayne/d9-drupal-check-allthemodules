# Description

There's no easy way to prevent the fields of type `changed` to be updated when
the host entity is updating. There are business cases when you don't want this
field to be refreshed on an entity save. A [Drupal core issue](
https://www.drupal.org/project/drupal/issues/2329253) is already dealing with
this problem but it's not yet committed. This module covers this lack of API and
will be dropped as soon the core issue is fixed.

# How to use it?

When saving an existing entity that has a `changed` field type, mark the field
to be preserved during the save:

```php
$node = Node::load(123);
// Change the title.
$node->title->value = 'New title';
// Mark the 'changed' field to be preserved.
$node->changed->preserve = TRUE;
$node->save();
```
