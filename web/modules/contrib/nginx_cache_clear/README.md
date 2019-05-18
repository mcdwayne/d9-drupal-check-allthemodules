
SUMMARY
-------

The Nginx Cache clear module helps to clear cache files that are created by
Fast CGI or Proxy module. CGI or Proxy module caches the file according to the
key generated based on URL. This module also helps to clear the cache file of
alias URLs or other related URLs of specific node by
hook_add_related_cached_url().

For a full description of the module, visit the project page:
  http://drupal.org/project/nginx_cache_clear

To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/nginx_cache_clear


REQUIREMENTS
------------
None.


INSTALLATION
------------
* Install as usual, see http://drupal.org/node/895232 for further information.

* Its better to add administration menu(admin_menu).


UNINSTALLTION:
--------------
1. Disable the module.
2. Uninstall the module.

CONFIGURATION
-------------
* Configure the NGINX settings in Administration >> Configuration >>
  System >> Nginx Cache Clear Settings

  - Configure the nginx settings in the module

CREDITS:
--------
This project has been sponsored by:
* Zyxware Technologies
  Specialized in consulting and planning of Drupal powered sites,
  Zyxware Technologies offers installation, development, theming,
  customization, and hosting to get you started. Visit http://www.zyxware.com
  for more information.
