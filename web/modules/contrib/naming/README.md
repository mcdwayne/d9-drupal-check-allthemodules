
Table of Contents
-----------------

* Introduction
* Features
* Demo
* Notes
* References


Introduction
------------

> "There are only two hard things in Computer Science: cache invalidation and naming things." -- Phil Karlton


Features
--------

- Provides a naming convention details widget on config and content entity
  add forms.

- Allows automatic machine name generation to be disabled.
  (/admin/config/development/naming/settings)
    
- Provides a help page that outline all naming conventions.  
  (/admin/help/naming)


Demo
----

> Evaluate this project online using [simplytest.me](https://simplytest.me/project/naming).


Notes
-----

- Generally a naming convention id will match a route name.  
  Exceptions are:
    - `field_ui.field_storage_config_add` applies to all content entity add 
      field forms.
    - `views_ui.*` routes include the plugin type and value.
    
- The Webprofiler toolbar included in the [Devel](https://www.drupal.org/project/devel)
  module makes it very easy to determine the current route for any given page.

- All included Naming convention config entities assume that the web sites' 
  default filter format supports basic HTML which includes 
  `<p>, <ul>, and <li> tags`.


Author/Maintainer
-----------------

- [Jacob Rockowitz](http://drupal.org/user/371407)
