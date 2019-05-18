CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module provides a way to serve configurable plain text files.

The main idea is to provide a way to configure static files in the backend,
like facebook domain ownership, google site verification or ads.txt, which may
be added or changed regularly by SEOs.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/serve_plain_file

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/serve_plain_file


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


RECOMMENDED MODULES
-------------------

It can be either used as a normal configuration which will be imported/exported
or in conjunction with Config Ignore to make it manageable in the production
environment without being overwritten by config import.

 * Config Ignore - https://www.drupal.org/project/config_ignore


INSTALLATION
------------

 * Install the Serve plain file module as you would normally install a
   contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > System > Serve plain files
       for configuration.
    3. Add a new file with Max age, Path, and Content.
    4. The file is now available under the provided path

If you have external caching providers, e.g. Varnish, CDNs you should monitor
file changes via entity type hooks and purge caches there.

e.g:

``` 
use Drupal\serve_plain_file\Entity\ServedFile;

/**
 * Implements hook_ENTITY_TYPE_update().
 */
function my_module_served_file_update(ServedFile $entity) {
  $urls = $entity->getUrlsForCachePurging();
  my_module_purge_external_caches($urls);
}

/**
 * Implements hook_ENTITY_TYPE_delete().
 */
function my_module_served_file_delete(ServedFile $entity) {
  $urls = $entity->getUrlsForCachePurging();
  my_module_purge_external_caches($urls);
}
```

You can limit the MIME-types a backend-user can use via the the setting option
allowed_mime_types (@see serve_plain_file.settings.yml).

The selected MIME-type for a served file will be sent in the Content-Type 
response header. 

MAINTAINERS
-----------

 * Mathias (mbm80) - https://www.drupal.org/u/mbm80

Supporting organizations: 

 * drunomics - https://www.drupal.org/drunomics
