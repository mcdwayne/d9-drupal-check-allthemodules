OSInet TAXONEWS module for Drupal
=================================

UNSTABLE VERSION for Drupal 8.x

Caveat Emptor: everything in this branch is likely to change before Drupal 8 is
actually released. It is currently NOT fit for any need except core 8 testing.

PREREQUISITES
=============

  - Drupal 8.0-beta1
  - PHP 5.4
  - MySQL 5.x

INSTALLING
==========

1. Copy this directory to `<drupal>/modules/taxonews`

2. To create a new vocabulary for taxonews: (skip otherwise)
   * browse to `admin/structure/taxonomy/add` and define a vocabulary for your news
   * add terms to your new vocabulary
3. To create news blocks with taxonews:
  * Browse to `admin/structure/taxonomy/taxonews`
  * Select the vocabularies for which Taxonews will create blocks. Remember, one
    block will be defined for each term in the vocabulary.
  * Define the maximum age, in days, for nodes to be considered as news included in taxonews blocks
  * Browse to `admin/structure/block`
  * If your site has several themes enabled, click on the tab for the theme for
    which you want blocks to appear
  * Click on the name of the block you want to place in the `+Place blocks` list.
  * Place the blocks you want like any other Drupal block.

4. To theme blocks:
  * For plain PHP themes, define <your_theme>_taxonews_block_view()
    and look at the code for theme_taxonews_block_view to see what
    your options are.
  * For PHPtemplate themes, see http://drupal.org/node/11811 and redefine
    theme_taxonews_block_view()
  * For simple CSS customizations, themeability is defined to the read-more class,
    the list styles (theme_item_list) and RSS icon styles (theme_feed_icon())


UPGRADING FROM DRUPAL 4.6/4.7/5/6/7 BRANCHES
==========================================

This module does not store any content except its configuration, so you can just
erase the files from the previous version and copy the files of the new version.
No additional install or update procedure is currently necessary.


UNINSTALLING
============

Use the standard Drupal 8 uninstall procedure: no trace of the module will remain.


Legal notice
============

This documentation is:

* Copyright 2005-2014 Ouest Systemes Informatiques.
* Licensed under the Creative Commons BY-NC-SA 2.0 license for France,
  found at http://creativecommons.org/licenses/by-nc-sa/2.0/fr
