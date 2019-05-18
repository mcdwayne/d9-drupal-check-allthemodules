# Blizz Bulk Creator

## Summary

The Blizz Bulk Creator module makes creating large numbers of entities
easier. It lets users enter a value in a single field and fills other
fields with customizable default values.

## Requirements

Currently, Blizz Bulk Creator only provides for the creation of media entities
and therefore depends upon media_entity. In a future version this restriction
is going to be removed.

## Installation

Installation as usual: Place the directory of this module within /modules
(or maybe /modules/contrib). When using composer use `composer require
drupal/blizz_bulk_creator`.

Enable the module by navigating to Administer > Extend and checking the
"Enabled" checkbox on "Blizz Bulk Creator" (or simply use drush: `drush en 
blizz_bulk_creator`).

## Known Issues

- Anonymous users

  To make this module work flawlessly for anonymous users, 
the application of the following core patch is required:
https://www.drupal.org/files/issues/saving_to_private-2743931-14.patch

  See the corresponding issue on drupal.org:
https://www.drupal.org/node/2743931

- May enable users to create restricted entities

  In its current development state this module enables users to create 
entities to which they may not have the appropriate permissions, because
it implements only very rough permissions without checking the presence of
"nested permissions".

- May have an impact on site performance

  Currently, this module implements hook_form_alter in order to embed the
  bulkcreate interfaces into enabled entities. Because this hook literally
  affects all existing forms on a site it may slow down the site performance
  if there are also forms that are going to be presented to anonymous users.
  
  Note: in a future version this hook will be removed.
