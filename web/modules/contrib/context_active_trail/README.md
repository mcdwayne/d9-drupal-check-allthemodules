Introduction
------------

Context Active Trail sets the active trail and breadcrumbs for a page based on
the context it is in. For example, you can make every node of type _article_
appear to live under the _Blog_ menu item.

* For a full description of the module, visit the
  [project page](https://www.drupal.org/project/context_active_trail)

* [To submit bug reports and feature suggestions, or to track changes]
  (https://www.drupal.org/project/issues/2798989)


Requirements
------------

This module requires the following modules:

 * Context (https://drupal.org/project/context)


Recommended modules
-------------------

 * Context UI, part of Context, allows this module to be configured in the
   user interface.


Installation
------------

 * Install [as you would normally install a contributed Drupal module]
(https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules).

 * This module is not compatible with other modules that attempt to take
   control of active trails, such as:

    * [Menu Trail By Path](https://www.drupal.org/project/menu_trail_by_path)


Configuration
-------------

With Context UI installed, create or modify contexts at
Administration » Structure » Contexts.

* For each context, you may add a Reaction of the type Active Trail.
* Choose a menu item to set the active trail of matching requests,.
* Optionally enable setting breadcrumbs as well.


Maintainers
-----------

Current maintainers:

 * [Dave Vasilevsky (vasi)](https://www.drupal.org/user/390545)

This project has been sponsored by:

 * [Evolving Web](https://evolvingweb.ca)
