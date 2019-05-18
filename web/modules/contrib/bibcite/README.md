INTRODUCTION
------------

 **Bibliography & Citation module is under active development. It is not ready for use on production sites and and breaking changes are possible until Beta**
 At the current moment implemented basic features are render, export and import. Here is a list of modules included in the project and their features, that are already implemented:

 **Bibliography & Citation**
 It is a core module that provides API for a render of bibliography citation. The library we used is from official CSL style repository with over 8000 styles. Those styles are available without charge under a Creative Commons Attribution-ShareAlike (BY-SA) license.

 **Bibliography & Citation - Entity**
 Implements storage for bibliographic data as Drupal entities: Reference, Contributor and Keyword. Reference entity can be rendered as citations, exported and imported.

 **Bibliography & Citation - Export** 
 Provides the possibility to export bibliographic content. Adds export links to citations (configurable)

 **Bibliography & Citation - Import** 
 Provides import feature and UI for import from files.

 **Bibliography & Citation - BibTeX**
 Provides possibility to use BibTeX format for import and export.

 **Bibliography & Citation - Endnote**
  Provides possibility to use EndNote 7 XML, EndNote X3 XML and EndNote Tagged formats for import and export.

 **Bibliography & Citation - Marc**
 Provides possibility to use MARC format for import and export.

 **Bibliography & Citation - RIS**
 Provides possibility to use RIS format for import and export.

 * For a full description of the module, visit the project page:  
   https://drupal.org/project/bibcite

 * To submit bug reports and feature suggestions, or to track changes:  
   https://drupal.org/project/issues/bibcite


REQUIREMENTS
------------

This module requires the following libraries:

 * "academicpuma/citeproc-php": "~1.0",
 * "adci/full-name-parser": "^0.2",
 * "technosophos/LibRIS": "~2.0",
 * "audiolabs/bibtexparser": "dev-master",
 * "caseyamcl/php-marc21": "~1.0"

 Some of these libraries are required by submodules which provide additional formats for import and export.


RECOMMENDED MODULES
-------------------

 * [Bibliography & Citation - Migrate](https://www.drupal.org/project/bibcite_migrate)  
   Allows to migrate your bibliographic data from the Bibliography (biblio) module.

 * [Bibliography & Citation - Altmetric](https://www.drupal.org/project/bibcite_altmetric)  
   Adds [Altmetric](https://www.altmetric.com) badges to reference entities.

 * [Metatag Google Scholar](https://www.drupal.org/project/metatag_google_scholar)  
   Provides number of meta tags to help with indexing of scholarly
   content/articles in [Google Scholar](https://scholar.google.com).


INSTALLATION
------------

 * If you [manage your site dependencies via Composer](https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies)
   then the module's dependencies will be installed automatically once the module itself is installed
   via Composer.

 * In case you manage your site dependencies manually or via Drush,
   install required libraries via [Composer](https://www.drupal.org/docs/8/extending-drupal/installing-modules-composer-dependencies)
   using following command:

   `composer require academicpuma/citeproc-php:~1.0 adci/full-name-parser:^0.2 technosophos/LibRIS:~2.0 audiolabs/bibtexparser:dev-master caseyamcl/php-marc21:~1.0`

   You can find a bit more info about Composer [here](https://www.drupal.org/node/2804889#comment-11651131)

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules
   for further information.


MAINTAINERS
-----------

Current maintainers:
 * Anton Shubkin (antongp) - https://www.drupal.org/u/antongp
 * adci_contributor - https://www.drupal.org/u/adci_contributor

This project has been sponsored by [ADCI Solutions](http://www.adcisolutions.com/)
