CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

A utility module which will delete all taxonomy terms in a vocabulary. Deleting
taxonomies can be a very frustrating issue specially when there are a lot to
delete for testing purposes.

The module provides an UI where you can select the Vocabulary from which the
taxonomy has to be deleted. Additionally for developers there is a Drush command
which will delete all taxonomy terms from a Vocabulary.

for more info visit
https://www.drupal.org/sandbox/malabya/2755573

REQUIREMENTS
------------

This module does not have any dependency.

INSTALLATION
------------

* Install as you would normally install a contributed Drupal module. See:
  https://www.drupal.org/documentation/install/modules-themes/modules-8
  for further information.

CONFIGURATION
-------------

* Copy the "taxonomy_delete" directory under "modules/";
* Go to "admin/modules" and enable "Taxonomy delete" module;
* Go to "admin/structure/taxonomy_delete" and select the vocubalary for which
  you want to delete the taxonomy terms.

DRUSH USAGE
-----------

 This modules supports drush to delete all taxonomy terms from a Vocabulary

   * drush term-delete {vocabulary-name} -  TO delete all taxonomy terms from
     the specified vocabulary
      
     using alias                
  *  drush tdel {vocabulary-name} 
  

MAINTAINERS
-----------

Current maintainers:
 * Malabya Tewari (malavya) - https://www.drupal.org/u/malavya
