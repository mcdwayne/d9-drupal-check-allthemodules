# Entity Embed Extras

This module adds extra options to customize the Entity Embed Dialog.

# INTRODUCTION

1) You can choose a custom dialog title to display during the selection step.
2) You can choose a custom dialog title to display during the embed step.
3) You can choose a custom label for the "back" button that returns to the
selection step.
4) You can select a view to display the entity embed, which allows for a
nicer UI experience for the editor. You can customize the view (or create
your own) to add images and an edit button (a default view is available out
of the box with the module).

## REQUIREMENTS

* Drupal 8
* [Entity Embed](https://www.drupal.org/project/entity_embed) module

## INSTALLATION

Entity Embed Extras can be installed via the
[standard Drupal installation process](http://drupal.org/node/895232).

## CONFIGURATION

* Install and enable Entity Embed module.
  [Admin Toolbar](https://www.drupal.org/project/entity_embed)
* Install and enable Entity Embed Extras module.
  [Entity toolbar](https://www.drupal.org/project/entity_embed_extras)

## USAGE

* After you have enabled the module, edit all of your entity embed buttons, 
/admin/config/content/embed.  Each will have new options to set the display
of entities in the dialog as well as the titles.  Customize the options
and then save the configs.

## MAKING ADDITIONAL CHANGES

* You can make your own customizations to the the dialogs using hook
hook_form_FORM_ID_alter() in a custom module.
