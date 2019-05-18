INTRODUCTION
------------

[Bibliography & Citation - Crossref](https://www.drupal.org/project/bibcite_crossref) module provides DOI lookup
functionality for the [Bibliography & Citation](https://www.drupal.org/project/bibcite) module.

 * For a full description of the module, visit the project page:  
   https://www.drupal.org/project/bibcite_crossref

 * To submit bug reports and feature suggestions, or to track changes:  
   https://www.drupal.org/project/issues/bibcite_crossref


REQUIREMENTS
------------

This module requires the following modules:

 * [Bibliography & Citation](https://www.drupal.org/project/bibcite)

This module also requires the following library:

 * "renanbr/crossref-client": "^1.0"


INSTALLATION
------------

 * If you [manage your site dependencies via Composer](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies)
   then the module's dependencies will be installed automatically once the module itself is installed
   via Composer.

 * In case you manage your site dependencies manually or via Drush,
   install required libraries via [Composer](https://www.drupal.org/docs/8/extending-drupal/installing-modules-composer-dependencies)
   using following command:

   `composer require renanbr/crossref-client:^1.0`

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules
   for further information.


CONFIGURATION
-------------

 * Configure contact information in Administration » Configuration » Bibliography & Citation » Crossref.  
   It's recommended to configure contact information which will be passed with API queries. If provided, API queries
   will be directed to a special pool of API machines that are reserved for polite users. This way you can be contacted
   if Crossref sees a problem.


MAINTAINERS
-----------

Current maintainers:

 * Anton Shubkin (antongp) - https://www.drupal.org/u/antongp
 * adci_contributor - https://www.drupal.org/u/adci_contributor

This project has been sponsored by [ADCI Solutions](https://www.adcisolutions.com/)
