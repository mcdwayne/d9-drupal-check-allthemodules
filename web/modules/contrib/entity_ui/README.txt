INTRODUCTION
------------

The Entity UI Builder module provides an admin UI for creating local tasks
(tabs) on content entities. Tabs can contain anything; their output is created
by different plugins. This module contains the following tab content plugins:

* entity forms, showing a given form mode
* entity display, showing a given view mode
* forms for configurable actions, allowing the action to be executed immediately
  by the user.
* form to change the owner of the entity.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------

 * The UI for configuring tabs for an entity type is alongside its existing
   admin UI:

   - Entity types which use bundles get an extra tab beside the bundles list.
     For example, entity tabs on nodes can be configured at
     admin/structure/types/entity_ui.

   - Entity types which do not use bundles but are fieldable get an extra tab
     alongside their field admin UI.
