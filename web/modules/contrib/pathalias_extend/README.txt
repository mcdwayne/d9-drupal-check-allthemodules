CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * FAQ
 * Maintainers


INTRODUCTION
------------

The Pathalias Extend module allows you to extend existing path aliases of
content entities with suffixes matching a particular pattern and optionally
create an alias for the suffix, if it doesn't exist, yet.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/pathalias_extend

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/node/add/project-issue/pathalias_extend


REQUIREMENTS
------------

This module requires PHP version 7.0 or later.

This module depends on the path module provided by Drupal Core.


RECOMMENDED MODULES
-------------------

Currently none.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

Let's imagine, that we set up our Drupal with a content type called 'Member'.
Each member node has a path alias like '/member/node-title'. There also is a
route with the internal path '/node/{node}/contact'. Now we would like to render
existing links to that internal path as '/member/node-title/contact' instead of
'/node/1234/contact'. If a visitor accesses the URL '/member/node-title/contact'
we would like the same content to appear, as if the visitor entered the URL
'/node/1234/contact'. Let's see how we would set up something like that:

  * Make sure, that you have the permission to configure Pathalias Extend.
  * Visit Configuration > Search and metadata > URL aliases > Extend.
  * Click on the "Add suffix" button.
  * Choose an administrative label and a machine name for your new suffix.
  * Select 'Content' as Target entity type and 'Member' as Target bundle.
  * Enter '/contact' as the pattern for the suffix.
  * Until you are sure that everything works as expected, you probably don't
    want to create aliases on the fly, so leave this unchecked for now. You can
    enable it later for better performance.
  * Make sure, that the suffix is enabled.
  * Save.
  * Clear all your caches.


FAQ
---

Q: Why do I get 301 redirects to the unaliased path for an extended alias?

A: You probably have redirect module and its route normalizer enabled. To
   prevent the redirect, set the _disable_route_normalizer attribute to TRUE for
   any routes targeted by your extended aliases. You will only need to do this,
   if the "Create alias, if missing" setting is disabled for your bundle.

Q: Why do I get 404 errors for my extended alias?

A: You need to clear the cache after changing anything on the configuration page
   of Pathalias Extend.

Q: Why are my internal URLs for my suffixes not rewritten to the extended alias?

A: You need to make sure that the internal URLs are passed through Drupal's 
   outbound path processor, e.g. by using \Drupal\Core\Link, \Drupal\Core\Url or
   \Drupal\Core\Render\Element\Link.


MAINTAINERS
-----------

Current maintainers:
 * Patrick Fey (feyp) - https://drupal.org/u/feyp

This project has been partly sponsored by:
 * werk21 GmbH
   werk21 is a full service agency from Berlin, Germany, for politics,
   government, organizations and NGOs. Together with its customers,
   werk21 has realized over 60 Drupal web sites (since version 5).
   Visit https://www.werk21.de for more information.
