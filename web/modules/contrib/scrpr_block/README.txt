DESCRIPTION
===========
Provides configurable blocks with a link. The link text is scraped from the content to which the URL
points.

This is useful if you want to link to a site that updates it's content regularly, but does not provide a
feed. The content of the link text is retrieved from the URL and selected by the configured CSS selector.
If no selector is provided the title element is selected by default.

INSTALLATION
============
1. Install as usual, see https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure for further information.
2. The module has no special configuration. All settings are available in the
   block settings:
   /admin/structure/block

MAINTAINER
==========
- dweichert (David Weichert)
