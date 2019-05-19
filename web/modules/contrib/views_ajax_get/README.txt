CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module will make your ajax-enabled Views, use GET instead of POST.

Why?
Because GET is much better for caching. If you're using something like Varnish,
or Boost, these (by default) will not store POST requests (and so they
shouldn't).

Views uses the Drupal Ajax framework. This by default uses POST.
While a lot of Ajax inside of Drupal requires POST (think the Views UI, for
example), a view itself doesn't. If you disable Ajax on a View, it will use
GET anyway.

How?
This module overrides a core Drupal Ajax JavaScript function (this is sometimes
referred to as monkey-patching).

Note:
Drupal sends additional data in the POST request about the current page state.
This includes a list of all the libraries included on the current page.
Because of the size this can make the request can be too large when using GET,
they are not included when using a GET request.


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Views Ajax Get module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------
 * Once this module is enabled, enable the Views ajax get display extender
   (Home >> Administration >> Structure >> Views >> Views settings >>
   Advanced Views settings).
 * Enable on each view by editing the "Use AJAX" setting, and enabling GET.


MAINTAINERS
-----------

Current maintainers:
 * Leon Kessler (leon.nk) - http://drupal.org/user/595374
