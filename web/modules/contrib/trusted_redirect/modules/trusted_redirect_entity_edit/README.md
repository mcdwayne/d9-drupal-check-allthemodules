Trusted redirect entity edit.
================================================================================

In Drupal every content entity is using its own edit url which requires entity
id as route parameter (node/1/edit, taxonomy_term/2/edit). This may cause
troubles if you sync multiple systems and ids between them most probably won't
match. What should match even between different systems is the uuid of entity.
And that's where this modules steps in.

Module introduces a new general route for editing content entities. Instead of
specific entity type routes it introduces one single entity edit route with the
pattern `entity/{uuid}/url`. All you need now is uuid of your content entity.

With such a general route you can easily access the editing of the content
entity on the very same route on all synced Drupal instances (satellite sites).
